<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V179 — Cuenta de la que salió el pago en efectivo de una nómina (crédito del asiento).
 *
 * Traducción de la migración Flyway V179__nomina_cuenta_pago.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE nomina ADD COLUMN IF NOT EXISTS cuenta_pago_id BIGINT
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE nomina DROP COLUMN IF EXISTS cuenta_pago_id');
    }
};
