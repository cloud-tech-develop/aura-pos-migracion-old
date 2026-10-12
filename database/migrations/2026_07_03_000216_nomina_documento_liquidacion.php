<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V216 — Liquidación de la prenómina por documento (rediseño R3.2c).
 *
 * Traducción de la migración Flyway V216__nomina_documento_liquidacion.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE nomina ADD COLUMN IF NOT EXISTS liquidada_at TIMESTAMP
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE nomina ADD COLUMN IF NOT EXISTS error_liquidacion VARCHAR(500)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE proceso_nomina DROP CONSTRAINT IF EXISTS chk_proc_tipo
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE proceso_nomina ADD CONSTRAINT chk_proc_tipo CHECK (tipo IN ('LIQUIDACION_PERIODO',
    'LIQUIDACION_PRESTACIONES', 'NOMINA_ELECTRONICA', 'NOMINA_ELECTRONICA_AJUSTE', 'PILA_GENERACION',
    'CONTABILIZACION', 'IMPORTACION', 'LIQUIDACION_DOCUMENTO'))
MIG_SQL);
    }

    public function down(): void
    {
        // Sin reversa: las columnas guardan el resultado de liquidaciones ya hechas.
    }
};
