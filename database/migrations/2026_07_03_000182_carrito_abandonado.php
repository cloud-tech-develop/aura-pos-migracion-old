<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V182 — Carritos del POS vaciados sin vender (carrito_abandonado + items).
 *
 * Traducción de la migración Flyway V182__carrito_abandonado.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS carrito_abandonado (
    id                 BIGSERIAL PRIMARY KEY,
    empresa_id         INT           NOT NULL,
    sucursal_id        INT,
    turno_caja_id      BIGINT,
    usuario_id         INT,
    cliente_id         BIGINT,
    motivo             VARCHAR(20)   NOT NULL,
    iniciado_at        TIMESTAMP     NOT NULL,
    vaciado_at         TIMESTAMP     NOT NULL,
    duracion_segundos  INT           NOT NULL,
    items              INT           NOT NULL,
    total              NUMERIC(15,2) NOT NULL,
    created_at         TIMESTAMP     NOT NULL DEFAULT now()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_carrito_abandonado_empresa_fecha
    ON carrito_abandonado (empresa_id, vaciado_at)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS carrito_abandonado_item (
    id              BIGSERIAL PRIMARY KEY,
    carrito_id      BIGINT        NOT NULL REFERENCES carrito_abandonado(id) ON DELETE CASCADE,
    producto_id     BIGINT,
    presentacion_id BIGINT,
    nombre          VARCHAR(255)  NOT NULL,
    cantidad        NUMERIC(15,4) NOT NULL,
    precio          NUMERIC(15,2) NOT NULL,
    subtotal        NUMERIC(15,2) NOT NULL,
    agregado_at     TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_carrito_abandonado_item_carrito
    ON carrito_abandonado_item (carrito_id)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS carrito_abandonado_item');
        DB::statement('DROP TABLE IF EXISTS carrito_abandonado');
    }
};
