<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V92 · E7 · Dimensiones contables proyecto/frente (C5): habilita rentabilidad
 * por obra. Columnas explícitas (no tabla genérica de dimensiones — sería
 * sobre-ingeniería a este tamaño). Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE asiento_detalle ADD COLUMN IF NOT EXISTS proyecto_id BIGINT');
        DB::statement('ALTER TABLE asiento_detalle ADD COLUMN IF NOT EXISTS frente_id BIGINT');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_asiento_detalle_proyecto ON asiento_detalle (proyecto_id) WHERE proyecto_id IS NOT NULL');

        // Compras y gastos pueden imputarse a un proyecto/frente desde el form.
        DB::statement('ALTER TABLE compra ADD COLUMN IF NOT EXISTS proyecto_id BIGINT');
        DB::statement('ALTER TABLE compra ADD COLUMN IF NOT EXISTS frente_id BIGINT');

        DB::statement('ALTER TABLE gasto ADD COLUMN IF NOT EXISTS proyecto_id BIGINT');
        DB::statement('ALTER TABLE gasto ADD COLUMN IF NOT EXISTS frente_id BIGINT');

        // La venta hereda el centro de costo de su sucursal (parametrizable).
        DB::statement('ALTER TABLE sucursal ADD COLUMN IF NOT EXISTS centro_costo_id BIGINT');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_asiento_detalle_proyecto');
        DB::statement('ALTER TABLE asiento_detalle DROP COLUMN IF EXISTS proyecto_id');
        DB::statement('ALTER TABLE asiento_detalle DROP COLUMN IF EXISTS frente_id');
        DB::statement('ALTER TABLE compra DROP COLUMN IF EXISTS proyecto_id');
        DB::statement('ALTER TABLE compra DROP COLUMN IF EXISTS frente_id');
        DB::statement('ALTER TABLE gasto DROP COLUMN IF EXISTS proyecto_id');
        DB::statement('ALTER TABLE gasto DROP COLUMN IF EXISTS frente_id');
        DB::statement('ALTER TABLE sucursal DROP COLUMN IF EXISTS centro_costo_id');
    }
};
