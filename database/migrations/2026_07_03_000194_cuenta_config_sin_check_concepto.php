<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V194 — Quita la restricción CHECK de cuenta_config.concepto.
 *
 * Traducción de la migración Flyway V194__cuenta_config_sin_check_concepto.sql
 * (aura-back-old). Idempotente.
 *
 * Hibernate la generó con la lista vieja de conceptos y ninguna migración la
 * mantiene: los conceptos nuevos (RETEIVA_ASUMIDA…) fallaban al sembrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE cuenta_config DROP CONSTRAINT IF EXISTS cuenta_config_concepto_check
MIG_SQL);
    }

    public function down(): void
    {
        // Sin vuelta atrás: la restricción vieja no conocía los conceptos actuales.
    }
};
