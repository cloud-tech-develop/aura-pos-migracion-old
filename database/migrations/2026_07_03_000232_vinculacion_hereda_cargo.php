<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V232 — La vinculación hereda la plantilla de su cargo: marca excluido en
 * vinculacion_concepto y vinculacion_novedad_permitida (docs/PLAN_CONCEPTOS_CARGOS_NOMINA.md, F6).
 * 
 * Traducción de la migración Flyway V232__vinculacion_hereda_cargo.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE vinculacion_concepto ADD COLUMN IF NOT EXISTS excluido BOOLEAN NOT NULL DEFAULT FALSE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE vinculacion_novedad_permitida ADD COLUMN IF NOT EXISTS excluido BOOLEAN NOT NULL DEFAULT FALSE
MIG_SQL);
    }

    public function down(): void
    {
        // Sin reversa: migración aditiva sobre datos en uso.
    }
};
