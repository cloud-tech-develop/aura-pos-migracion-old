<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V207 — Pago de declaraciones: redondeo a miles, intereses de mora y sanción.
 *
 * Traducción de la migración Flyway V207__declaracion_pago_redondeo_sancion.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE declaracion_pago ADD COLUMN IF NOT EXISTS ajuste_redondeo NUMERIC(18,2) NOT NULL DEFAULT 0
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE declaracion_pago ADD COLUMN IF NOT EXISTS intereses NUMERIC(18,2) NOT NULL DEFAULT 0
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE declaracion_pago ADD COLUMN IF NOT EXISTS sancion NUMERIC(18,2) NOT NULL DEFAULT 0
MIG_SQL);
    }

    public function down(): void
    {
        // Aditiva: no se revierte.
    }
};
