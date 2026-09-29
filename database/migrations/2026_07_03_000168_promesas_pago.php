<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V168 — promesas de pago con seguimiento.
 * 
 * Una gestión con resultado PROMESA_PAGO queda PENDIENTE y el sistema la resuelve:
 * CUMPLIDA si entran abonos del cliente por el monto prometido entre la gestión y la
 * fecha prometida; INCUMPLIDA si pasa la fecha sin completarse; CANCELADA si una
 * promesa nueva la reemplaza. Las promesas viejas con fecha y monto se toman como
 * PENDIENTE para que la primera evaluación las resuelva.
 *
 * Traducción de la migración Flyway V168__promesas_pago.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE gestion_cobro ADD COLUMN IF NOT EXISTS estado_promesa VARCHAR(12)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE gestion_cobro ADD COLUMN IF NOT EXISTS monto_pagado_promesa NUMERIC(15,2)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE gestion_cobro ADD COLUMN IF NOT EXISTS promesa_resuelta_at TIMESTAMP
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_gestion_cobro_estado_promesa') THEN
        ALTER TABLE gestion_cobro ADD CONSTRAINT ck_gestion_cobro_estado_promesa
            CHECK (estado_promesa IS NULL OR estado_promesa IN ('PENDIENTE','CUMPLIDA','INCUMPLIDA','CANCELADA'));
    END IF;
END $$
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE gestion_cobro SET estado_promesa = 'PENDIENTE'
WHERE estado_promesa IS NULL AND resultado = 'PROMESA_PAGO'
  AND fecha_promesa_pago IS NOT NULL AND COALESCE(monto_prometido, 0) > 0
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_gestion_cobro_promesa
    ON gestion_cobro (empresa_id, estado_promesa, fecha_promesa_pago)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_gestion_cobro_tercero_fecha
    ON gestion_cobro (empresa_id, tercero_id, created_at DESC)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_gestion_cobro_tercero_fecha');
        DB::statement('DROP INDEX IF EXISTS idx_gestion_cobro_promesa');
        DB::statement('ALTER TABLE gestion_cobro DROP CONSTRAINT IF EXISTS ck_gestion_cobro_estado_promesa');
        DB::statement('ALTER TABLE gestion_cobro DROP COLUMN IF EXISTS promesa_resuelta_at');
        DB::statement('ALTER TABLE gestion_cobro DROP COLUMN IF EXISTS monto_pagado_promesa');
        DB::statement('ALTER TABLE gestion_cobro DROP COLUMN IF EXISTS estado_promesa');
    }
};
