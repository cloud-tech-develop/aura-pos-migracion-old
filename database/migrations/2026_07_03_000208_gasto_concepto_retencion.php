<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V208 — Gasto: concepto de retención (cuenta y acreedor por concepto, como compras V203).
 *
 * Traducción de la migración Flyway V208__gasto_concepto_retencion.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE gasto ADD COLUMN IF NOT EXISTS retefuente_tarifa_id BIGINT REFERENCES tarifa_retencion(id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE gasto ADD COLUMN IF NOT EXISTS reteica_tarifa_id BIGINT REFERENCES tarifa_retencion(id)
MIG_SQL);
    }

    public function down(): void
    {
        // Aditiva: no se revierte.
    }
};
