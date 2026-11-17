<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V242 — Marcaje desde Excel: descanso por día y origen de la asistencia del frente.
 *
 * Traducción de la migración Flyway V242__asistencia_frente_excel.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE asistencia_frente_detalle ADD COLUMN IF NOT EXISTS minutos_descanso INT');

        DB::statement("ALTER TABLE asistencia_frente ADD COLUMN IF NOT EXISTS origen VARCHAR(20) NOT NULL DEFAULT 'DIGITACION'");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE asistencia_frente DROP COLUMN IF EXISTS origen');

        DB::statement('ALTER TABLE asistencia_frente_detalle DROP COLUMN IF EXISTS minutos_descanso');
    }
};
