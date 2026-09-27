<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V162 — presentaciones en compra, merma y obsequio; venta por unidad.
 *
 * Traducción de la migración Flyway V162__presentacion_en_documentos.sql (aura-back-old).
 *
 * cantidad y costo_unitario del detalle siguen en unidad base; lo escrito en la
 * presentación (4 pacas a $52.500) se guarda aparte. producto.vende_por_unidad =
 * false hace que el POS solo ofrezca las presentaciones. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE producto
    ADD COLUMN IF NOT EXISTS vende_por_unidad BOOLEAN NOT NULL DEFAULT true
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN producto.vende_por_unidad IS
    'false = el POS solo ofrece sus presentaciones; la unidad suelta no se vende.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE compra_detalle
    ADD COLUMN IF NOT EXISTS producto_presentacion_id BIGINT REFERENCES producto_presentacion (id),
    ADD COLUMN IF NOT EXISTS cantidad_presentacion    NUMERIC(18,6),
    ADD COLUMN IF NOT EXISTS costo_presentacion       NUMERIC(18,2)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN compra_detalle.cantidad_presentacion IS
    'Cantidad escrita en la presentación (4 pacas). La cantidad en unidad base está en cantidad.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE merma_detalle
    ADD COLUMN IF NOT EXISTS producto_presentacion_id BIGINT REFERENCES producto_presentacion (id),
    ADD COLUMN IF NOT EXISTS cantidad_presentacion    NUMERIC(18,6)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE obsequio_detalle
    ADD COLUMN IF NOT EXISTS producto_presentacion_id BIGINT REFERENCES producto_presentacion (id),
    ADD COLUMN IF NOT EXISTS cantidad_presentacion    NUMERIC(18,6)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE obsequio_detalle DROP COLUMN IF EXISTS cantidad_presentacion, DROP COLUMN IF EXISTS producto_presentacion_id');
        DB::statement('ALTER TABLE merma_detalle DROP COLUMN IF EXISTS cantidad_presentacion, DROP COLUMN IF EXISTS producto_presentacion_id');
        DB::statement('ALTER TABLE compra_detalle DROP COLUMN IF EXISTS costo_presentacion, DROP COLUMN IF EXISTS cantidad_presentacion, DROP COLUMN IF EXISTS producto_presentacion_id');
        DB::statement('ALTER TABLE producto DROP COLUMN IF EXISTS vende_por_unidad');
    }
};
