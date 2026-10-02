<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V188 — recargo por forma de pago.
 *
 * Sistecrédito, ADDI…: el cliente paga un recargo (porcentaje) sobre lo que
 * paga con esa forma. La venta lo agrega como línea de servicio RECARGO-FP.
 *
 * Traducción de la migración Flyway V188__recargo_forma_pago.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE forma_pago_contable
    ADD COLUMN IF NOT EXISTS recargo_porcentaje NUMERIC(7,4) NOT NULL DEFAULT 0
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_forma_pago_recargo') THEN
        ALTER TABLE forma_pago_contable
            ADD CONSTRAINT ck_forma_pago_recargo CHECK (recargo_porcentaje >= 0 AND recargo_porcentaje <= 100);
    END IF;
END $$
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE forma_pago_contable DROP CONSTRAINT IF EXISTS ck_forma_pago_recargo');
        DB::statement('ALTER TABLE forma_pago_contable DROP COLUMN IF EXISTS recargo_porcentaje');
    }
};
