<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V142 — de dónde sale (o entra) la plata, declarado explícitamente.
 *
 * Traducción de la migración Flyway V142__origen_fondos_explicito.sql
 * (aura-back-old).
 *
 * Hasta ahora el backend DEDUCÍA el origen del dinero a partir del usuario que
 * digitaba el documento: la compra buscaba el turno del comprador, el abono
 * usaba el que mandara el front, y el gasto ni preguntaba — siempre acreditaba
 * CAJA. Como el administrador no tiene turno, sus gastos y abonos no caían en el
 * cierre de ninguna caja, y un gasto pagado por transferencia dejaba la caja
 * contable en negativo con el banco intacto.
 *
 * El dinero pertenece a una caja o a una cuenta, nunca a un usuario. Estas
 * columnas son lo que faltaba para que cada documento lo declare:
 *   CAJA            → efectivo; exige turno abierto y mueve el arqueo
 *   BANCO           → cuenta_bancaria_id; afecta su cuenta contable y su saldo
 *   CUENTA CONTABLE → cuenta elegida a mano, o la de la forma de pago
 *
 * IMPORTANTE: el backend corre con ddl-auto=validate, así que no arranca hasta
 * que esta migración se aplique. Todo es NULL-able o con DEFAULT, así que los
 * documentos históricos siguen leyéndose igual. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Gasto ───────────────────────────────────────────────────────────
        // Ojo con el nombre: `gasto.cuenta_contable_id` YA EXISTE y significa la
        // cuenta de DÉBITO (a qué gasto se imputa). La nueva es el CRÉDITO — de
        // dónde salió la plata — y por eso se llama `cuenta_pago_id`.
        // Confundirlas invierte el asiento.
        DB::statement("ALTER TABLE gasto ADD COLUMN IF NOT EXISTS forma_pago         VARCHAR(20) NOT NULL DEFAULT 'CONTADO'");
        DB::statement("ALTER TABLE gasto ADD COLUMN IF NOT EXISTS metodo_pago        VARCHAR(30) NOT NULL DEFAULT 'EFECTIVO'");
        DB::statement('ALTER TABLE gasto ADD COLUMN IF NOT EXISTS cuenta_bancaria_id BIGINT');
        DB::statement('ALTER TABLE gasto ADD COLUMN IF NOT EXISTS cuenta_pago_id     BIGINT');

        // CONTADO paga ya (caja/banco/cuenta); CREDITO deja una cuenta por pagar.
        DB::statement('ALTER TABLE gasto DROP CONSTRAINT IF EXISTS chk_gasto_forma_pago');
        DB::statement("ALTER TABLE gasto ADD CONSTRAINT chk_gasto_forma_pago CHECK (forma_pago IN ('CONTADO', 'CREDITO'))");

        DB::statement('ALTER TABLE gasto DROP CONSTRAINT IF EXISTS fk_gasto_cuenta_bancaria');
        DB::statement('ALTER TABLE gasto ADD CONSTRAINT fk_gasto_cuenta_bancaria FOREIGN KEY (cuenta_bancaria_id) REFERENCES cuenta_bancaria(id)');

        DB::statement('ALTER TABLE gasto DROP CONSTRAINT IF EXISTS fk_gasto_cuenta_pago');
        DB::statement('ALTER TABLE gasto ADD CONSTRAINT fk_gasto_cuenta_pago FOREIGN KEY (cuenta_pago_id) REFERENCES plan_cuenta(id)');

        // El gasto histórico se registró siempre como pagado de contado en
        // efectivo, que es justo lo que dicen los DEFAULT. Nada que rellenar.

        // ── Pago de compra ──────────────────────────────────────────────────
        // Tercera vía para la compra: hasta ahora el pago solo podía apuntar a
        // una cuenta bancaria, así que sin caja abierta el administrador se
        // quedaba sin salida.
        DB::statement('ALTER TABLE compra_pago ADD COLUMN IF NOT EXISTS cuenta_contable_id BIGINT');
        DB::statement('ALTER TABLE compra_pago DROP CONSTRAINT IF EXISTS fk_compra_pago_cuenta_contable');
        DB::statement('ALTER TABLE compra_pago ADD CONSTRAINT fk_compra_pago_cuenta_contable FOREIGN KEY (cuenta_contable_id) REFERENCES plan_cuenta(id)');

        // ── Abonos ──────────────────────────────────────────────────────────
        // Mismo motivo en cartera: el administrador cobra o paga sin caja abierta.
        DB::statement('ALTER TABLE abonos_cobrar ADD COLUMN IF NOT EXISTS cuenta_contable_id BIGINT');
        DB::statement('ALTER TABLE abonos_pagar  ADD COLUMN IF NOT EXISTS cuenta_contable_id BIGINT');

        DB::statement('ALTER TABLE abonos_cobrar DROP CONSTRAINT IF EXISTS fk_abonos_cobrar_cuenta_contable');
        DB::statement('ALTER TABLE abonos_cobrar ADD CONSTRAINT fk_abonos_cobrar_cuenta_contable FOREIGN KEY (cuenta_contable_id) REFERENCES plan_cuenta(id)');

        DB::statement('ALTER TABLE abonos_pagar DROP CONSTRAINT IF EXISTS fk_abonos_pagar_cuenta_contable');
        DB::statement('ALTER TABLE abonos_pagar ADD CONSTRAINT fk_abonos_pagar_cuenta_contable FOREIGN KEY (cuenta_contable_id) REFERENCES plan_cuenta(id)');

        // ── Normalización del método de pago ────────────────────────────────
        // El cierre de caja compara `metodo_pago = 'EFECTIVO'` en SQL exacto,
        // pero el método se guardaba tal como lo mandara el front. Un 'efectivo'
        // en minúscula no sumaba al efectivo esperado y el cajero cerraba con un
        // sobrante que no existía. El backend ya normaliza al guardar; esto
        // arregla lo ya guardado.
        foreach (['venta_pago', 'compra_pago', 'abonos_cobrar', 'abonos_pagar'] as $tabla) {
            DB::statement("UPDATE {$tabla} SET metodo_pago = UPPER(TRIM(metodo_pago)) WHERE metodo_pago <> UPPER(TRIM(metodo_pago))");
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE abonos_pagar  DROP CONSTRAINT IF EXISTS fk_abonos_pagar_cuenta_contable');
        DB::statement('ALTER TABLE abonos_cobrar DROP CONSTRAINT IF EXISTS fk_abonos_cobrar_cuenta_contable');
        DB::statement('ALTER TABLE compra_pago   DROP CONSTRAINT IF EXISTS fk_compra_pago_cuenta_contable');
        DB::statement('ALTER TABLE gasto         DROP CONSTRAINT IF EXISTS fk_gasto_cuenta_pago');
        DB::statement('ALTER TABLE gasto         DROP CONSTRAINT IF EXISTS fk_gasto_cuenta_bancaria');
        DB::statement('ALTER TABLE gasto         DROP CONSTRAINT IF EXISTS chk_gasto_forma_pago');

        DB::statement('ALTER TABLE abonos_pagar  DROP COLUMN IF EXISTS cuenta_contable_id');
        DB::statement('ALTER TABLE abonos_cobrar DROP COLUMN IF EXISTS cuenta_contable_id');
        DB::statement('ALTER TABLE compra_pago   DROP COLUMN IF EXISTS cuenta_contable_id');

        DB::statement('ALTER TABLE gasto DROP COLUMN IF EXISTS cuenta_pago_id');
        DB::statement('ALTER TABLE gasto DROP COLUMN IF EXISTS cuenta_bancaria_id');
        DB::statement('ALTER TABLE gasto DROP COLUMN IF EXISTS metodo_pago');
        DB::statement('ALTER TABLE gasto DROP COLUMN IF EXISTS forma_pago');

        // La normalización a mayúsculas del método de pago no se revierte:
        // el casing original no se guardó en ninguna parte y volver atrás
        // rompería otra vez el cuadre del cierre.
    }
};
