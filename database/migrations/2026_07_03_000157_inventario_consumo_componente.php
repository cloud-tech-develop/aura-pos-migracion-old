<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V157 — lo que consumió un producto con receta fuera de la venta.
 *
 * Traducción de la migración Flyway V157__inventario_consumo_componente.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 *
 * Una merma o un obsequio de un producto con receta saca sus componentes, no
 * el padre. Esta tabla guarda qué salió por cada línea para que anular devuelva
 * exactamente eso aunque la receta cambie después, y para que el asiento
 * acredite el inventario de cada componente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS inventario_consumo_componente (
    id                BIGSERIAL PRIMARY KEY,
    empresa_id        INTEGER       NOT NULL,
    origen            VARCHAR(20)   NOT NULL,
    detalle_id        BIGINT        NOT NULL,
    producto_padre_id BIGINT        NOT NULL,
    producto_hijo_id  BIGINT        NOT NULL,
    cantidad          NUMERIC(18,6) NOT NULL,
    costo_unitario    NUMERIC(18,6) NOT NULL DEFAULT 0,
    created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_consumo_componente_origen CHECK (origen IN ('MERMA', 'OBSEQUIO', 'VENTA')),
    CONSTRAINT fk_consumo_componente_empresa FOREIGN KEY (empresa_id)        REFERENCES empresa(id),
    CONSTRAINT fk_consumo_componente_padre   FOREIGN KEY (producto_padre_id) REFERENCES producto(id),
    CONSTRAINT fk_consumo_componente_hijo    FOREIGN KEY (producto_hijo_id)  REFERENCES producto(id)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_consumo_componente_detalle
    ON inventario_consumo_componente (origen, detalle_id)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS inventario_consumo_componente');
    }
};
