<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V206 — Cierre de mes: guarda la lista de chequeo al cerrar (para el acta).
 *
 * Traducción de la migración Flyway V206__cierre_mes_chequeo.sql (aura-back-old).
 * Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE periodo_contable ADD COLUMN IF NOT EXISTS chequeo_cierre TEXT
MIG_SQL);
    }

    public function down(): void
    {
        // Aditiva: no se revierte.
    }
};
