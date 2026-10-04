<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V197 — Facturación: facturar desde cotización o pedido, y centro de costo.
 *
 * Traducción de la migración Flyway V197__factura_origen_centro_costo.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE factura_venta ADD COLUMN IF NOT EXISTS cotizacion_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE factura_venta ADD COLUMN IF NOT EXISTS pedido_vendedor_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE factura_venta ADD COLUMN IF NOT EXISTS centro_costo_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE factura_venta_detalle ADD COLUMN IF NOT EXISTS cotizacion_detalle_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE venta ADD COLUMN IF NOT EXISTS centro_costo_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_factura_venta_cotizacion ON factura_venta (cotizacion_id) WHERE cotizacion_id IS NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_factura_venta_pedido ON factura_venta (pedido_vendedor_id) WHERE pedido_vendedor_id IS NOT NULL
MIG_SQL);
    }

    public function down(): void
    {
        // Sin vuelta atrás automática.
    }
};
