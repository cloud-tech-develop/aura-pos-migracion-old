<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V152 — la nota crédito de compra es un documento NEGATIVO.
 *
 * Traducción de la migración Flyway V152__nota_credito_compra.sql
 * (aura-back-old).
 *
 * Hasta ahora `tipo_documento = 'NOTA_CREDITO'` era solo una etiqueta: la NC se
 * registraba idéntica a una factura de compra — SUMABA stock, actualizaba el
 * costo del producto, creaba una cuenta por pagar nueva y generaba un asiento
 * débito a inventario. Justo al revés de lo que hace una nota crédito, que
 * existe para anular mercancía que no llegó.
 *
 * Dos columnas para arreglarlo:
 *
 *   · compra_origen_id — la factura de compra que la NC corrige. Sin ella no se
 *     puede validar que no se acredite más cantidad de la comprada, ni saber
 *     contra qué cuenta por pagar cruzarla.
 *
 *   · destino_nota_credito — qué pasa con la plata:
 *       CRUCE_CXP          → baja la deuda de la factura origen (compra a crédito)
 *       DEVOLUCION_DINERO  → el proveedor devuelve la plata (entra a caja/banco)
 *       SALDO_A_FAVOR      → queda crédito con el proveedor para compras futuras
 *
 * Los importes y las cantidades de la NC se guardan en NEGATIVO, para que toda
 * suma que ya existía sobre `compra` se nete sola.
 *
 * IMPORTANTE: el backend corre con ddl-auto=validate, así que no arranca hasta
 * que esta migración se aplique. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE compra ADD COLUMN IF NOT EXISTS compra_origen_id BIGINT');
        DB::statement('ALTER TABLE compra ADD COLUMN IF NOT EXISTS destino_nota_credito VARCHAR(20)');

        DB::statement(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'fk_compra_origen'
                ) THEN
                    ALTER TABLE compra
                        ADD CONSTRAINT fk_compra_origen
                        FOREIGN KEY (compra_origen_id) REFERENCES compra (id);
                END IF;
            END $$;
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_compra_origen ON compra (compra_origen_id) WHERE compra_origen_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_compra_origen');
        DB::statement('ALTER TABLE compra DROP CONSTRAINT IF EXISTS fk_compra_origen');
        DB::statement('ALTER TABLE compra DROP COLUMN IF EXISTS destino_nota_credito');
        DB::statement('ALTER TABLE compra DROP COLUMN IF EXISTS compra_origen_id');
    }
};
