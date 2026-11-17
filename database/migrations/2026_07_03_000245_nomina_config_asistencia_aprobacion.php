<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V245 — ¿La asistencia por obra la aprueba un supervisor antes de la nómina?
 * FALSE: la prenómina toma los días registrados al liquidar, sin revisión.
 *
 * Traducción de la migración Flyway V245__nomina_config_asistencia_aprobacion.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE nomina_config ADD COLUMN IF NOT EXISTS asistencia_requiere_aprobacion BOOLEAN NOT NULL DEFAULT TRUE');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE nomina_config DROP COLUMN IF EXISTS asistencia_requiere_aprobacion');
    }
};
