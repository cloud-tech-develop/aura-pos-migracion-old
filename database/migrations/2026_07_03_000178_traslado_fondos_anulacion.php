<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V178 — Columnas de anulación del traslado de fondos (motivo, quién, cuándo).
 *
 * Traducción de la migración Flyway V178__traslado_fondos_anulacion.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE traslado_fondos ADD COLUMN IF NOT EXISTS motivo_anulacion VARCHAR(500)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE traslado_fondos ADD COLUMN IF NOT EXISTS anulado_por      INT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE traslado_fondos ADD COLUMN IF NOT EXISTS anulado_at       TIMESTAMP
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_traslado_fondos_destino_cuenta
    ON traslado_fondos (empresa_id, destino_cuenta_id)
    WHERE destino_cuenta_id IS NOT NULL
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_traslado_fondos_destino_cuenta');
        DB::statement('ALTER TABLE traslado_fondos DROP COLUMN IF EXISTS anulado_at');
        DB::statement('ALTER TABLE traslado_fondos DROP COLUMN IF EXISTS anulado_por');
        DB::statement('ALTER TABLE traslado_fondos DROP COLUMN IF EXISTS motivo_anulacion');
    }
};
