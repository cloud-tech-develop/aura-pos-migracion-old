<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V165 — las salidas descuentan lotes.
 *
 * Traducción de la migración Flyway V165__lotes_en_salidas.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 *
 * documento_lote guarda de qué lote salió o a cuál entró cada línea de venta,
 * merma, obsequio, consumo interno, traslado, devolución, reconteo y ajuste,
 * para que anular devuelva exactamente eso. Agrega a empresa si se bloquean
 * los lotes vencidos y con cuántos días de anticipación se avisa.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS documento_lote (
    id             BIGSERIAL     PRIMARY KEY,
    origen         VARCHAR(30)   NOT NULL,
    detalle_id     BIGINT        NOT NULL,
    lote_id        BIGINT        NOT NULL,
    cantidad_base  NUMERIC(18,6) NOT NULL,
    created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_documento_lote_lote FOREIGN KEY (lote_id) REFERENCES lote(id)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_documento_lote_detalle ON documento_lote (origen, detalle_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_documento_lote_lote ON documento_lote (lote_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS lotes_bloquear_vencidos BOOLEAN NOT NULL DEFAULT TRUE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS lotes_dias_alerta INTEGER NOT NULL DEFAULT 30
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE empresa DROP COLUMN IF EXISTS lotes_dias_alerta');
        DB::statement('ALTER TABLE empresa DROP COLUMN IF EXISTS lotes_bloquear_vencidos');
        DB::statement('DROP TABLE IF EXISTS documento_lote');
    }
};
