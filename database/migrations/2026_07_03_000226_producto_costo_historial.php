<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V226 — Historial del costo de cada producto: costo antes y después, stock y
 * documento que lo movió (compra, nota crédito, anulación, edición, ajuste manual,
 * receta, importación).
 *
 * Traducción de la migración Flyway V226__producto_costo_historial.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS producto_costo_historial (
    id               BIGSERIAL PRIMARY KEY,
    empresa_id       INT            NOT NULL REFERENCES empresa(id),
    producto_id      BIGINT         NOT NULL REFERENCES producto(id),
    fecha            TIMESTAMP      NOT NULL DEFAULT now(),
    origen           VARCHAR(30)    NOT NULL,
    documento_id     BIGINT,
    documento_numero VARCHAR(60),
    tercero_id       BIGINT,
    cantidad         NUMERIC(38,6),
    costo_entrada    NUMERIC(38,6),
    stock_antes      NUMERIC(38,6),
    stock_despues    NUMERIC(38,6),
    costo_anterior   NUMERIC(38,6),
    costo_nuevo      NUMERIC(38,6)  NOT NULL,
    usuario_id       BIGINT,
    observacion      VARCHAR(200)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS ix_producto_costo_historial_producto
    ON producto_costo_historial (producto_id, fecha DESC)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS producto_costo_historial');
    }
};
