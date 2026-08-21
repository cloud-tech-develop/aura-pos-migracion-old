<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V146 — qué cuentas pueden usarse como origen de un pago.
 *
 * Traducción de la migración Flyway V146__plan_cuenta_medio_pago.sql
 * (aura-back-old).
 *
 * V142 le dio al documento la vía "pagar contra una cuenta contable elegida a
 * mano", que es la salida del administrador que paga sin caja abierta. Pero la
 * validación era solo "que exista y esté activa": nada impedía pagar un gasto
 * acreditando una cuenta de ingresos y dejar el asiento sin sentido.
 *
 * Este flag marca las cuentas que representan dinero disponible — efectivo,
 * bancos, y las cuentas puente de fondos entregados a alguien que todavía no ha
 * legalizado. Es lo que alimenta el combo "¿de dónde sale la plata?" del front y
 * lo que el motor exige antes de aceptar una cuenta como contrapartida.
 *
 * El contador lo administra desde la pantalla del plan de cuentas: cuando cree
 * su CAJA MENOR bajo la 1105, la marca aquí y aparece disponible para pagar.
 *
 * IMPORTANTE: el backend corre con ddl-auto=validate, así que no arranca hasta
 * que esta migración se aplique. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE plan_cuenta ADD COLUMN IF NOT EXISTS es_medio_pago BOOLEAN NOT NULL DEFAULT FALSE');

        // Semilla: el disponible del PUC (11) en sus cuentas de movimiento.
        //   1105 Caja (y la caja menor que cuelgue de ella)
        //   1110 Bancos
        //   1120 Cuentas de ahorro
        // Se deja fuera 1115 (remesas en tránsito) a propósito: es una cuenta
        // puente de recaudo, no un medio con el que se paga.
        DB::statement(<<<'SQL'
            UPDATE plan_cuenta
               SET es_medio_pago = TRUE
             WHERE auxiliar = TRUE
               AND activa   = TRUE
               AND (codigo LIKE '1105%' OR codigo LIKE '1110%' OR codigo LIKE '1120%')
        SQL);

        DB::statement(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_plan_cuenta_medio_pago
                ON plan_cuenta (empresa_id, es_medio_pago)
             WHERE es_medio_pago = TRUE
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_plan_cuenta_medio_pago');
        DB::statement('ALTER TABLE plan_cuenta DROP COLUMN IF EXISTS es_medio_pago');
    }
};
