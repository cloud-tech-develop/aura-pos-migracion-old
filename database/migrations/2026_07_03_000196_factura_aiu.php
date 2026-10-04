<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V196 — Factura AIU para construcción (FV5 de docs/PLAN_FACTURACION.md).
 *
 * Traducción de la migración Flyway V196__factura_aiu.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 *
 * Porcentajes de Administración, Imprevistos y Utilidad (IVA solo sobre la
 * utilidad) en factura_venta, y aiu_tipo en las líneas que genera el AIU.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE factura_venta ADD COLUMN IF NOT EXISTS aiu BOOLEAN NOT NULL DEFAULT FALSE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE factura_venta ADD COLUMN IF NOT EXISTS aiu_administracion_pct NUMERIC(6,2) NOT NULL DEFAULT 0
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE factura_venta ADD COLUMN IF NOT EXISTS aiu_imprevistos_pct NUMERIC(6,2) NOT NULL DEFAULT 0
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE factura_venta ADD COLUMN IF NOT EXISTS aiu_utilidad_pct NUMERIC(6,2) NOT NULL DEFAULT 0
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE factura_venta ADD COLUMN IF NOT EXISTS aiu_iva_pct NUMERIC(6,2) NOT NULL DEFAULT 19
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE factura_venta_detalle ADD COLUMN IF NOT EXISTS aiu_tipo VARCHAR(15)
MIG_SQL);
    }

    public function down(): void
    {
        // Sin vuelta atrás automática.
    }
};
