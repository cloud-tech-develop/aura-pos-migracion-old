<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V243 — Configuración de la asistencia por obra: usa frentes, exige soporte y
 * forma de registrar las horas (MANUAL | EXCEL | AMBOS).
 *
 * Traducción de la migración Flyway V243__nomina_config_asistencia_obra.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE nomina_config ADD COLUMN IF NOT EXISTS asistencia_usa_frentes BOOLEAN NOT NULL DEFAULT TRUE');

        DB::statement('ALTER TABLE nomina_config ADD COLUMN IF NOT EXISTS asistencia_exige_soporte BOOLEAN NOT NULL DEFAULT TRUE');

        DB::statement("ALTER TABLE nomina_config ADD COLUMN IF NOT EXISTS asistencia_registro VARCHAR(12) NOT NULL DEFAULT 'AMBOS'");

        DB::statement('ALTER TABLE nomina_config DROP CONSTRAINT IF EXISTS chk_nc_asistencia_registro');

        DB::statement(<<<'MIG_SQL'
ALTER TABLE nomina_config ADD CONSTRAINT chk_nc_asistencia_registro
    CHECK (asistencia_registro IN ('MANUAL', 'EXCEL', 'AMBOS'))
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE nomina_config DROP CONSTRAINT IF EXISTS chk_nc_asistencia_registro');

        DB::statement('ALTER TABLE nomina_config DROP COLUMN IF EXISTS asistencia_registro');

        DB::statement('ALTER TABLE nomina_config DROP COLUMN IF EXISTS asistencia_exige_soporte');

        DB::statement('ALTER TABLE nomina_config DROP COLUMN IF EXISTS asistencia_usa_frentes');
    }
};
