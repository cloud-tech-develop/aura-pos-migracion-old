<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V240 — Quién y cuándo autorizó una novedad que pasa un tope.
 *
 * Traducción de la migración Flyway V240__nomina_novedad_autorizacion.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE nomina_novedad ADD COLUMN IF NOT EXISTS autorizada_por BIGINT');

        DB::statement('ALTER TABLE nomina_novedad ADD COLUMN IF NOT EXISTS autorizada_at TIMESTAMP');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE nomina_novedad DROP COLUMN IF EXISTS autorizada_at');

        DB::statement('ALTER TABLE nomina_novedad DROP COLUMN IF EXISTS autorizada_por');
    }
};
