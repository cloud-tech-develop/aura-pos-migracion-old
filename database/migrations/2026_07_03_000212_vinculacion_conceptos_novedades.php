<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V212 — Vinculación: acto administrativo, municipio de trabajo, conceptos y novedades permitidas.
 *
 * Traducción de la migración Flyway V212__vinculacion_conceptos_novedades.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS doc_interno_tipo VARCHAR(30)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS municipio_trabajo_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS vinculacion_concepto (
    id               BIGSERIAL     PRIMARY KEY,
    contrato_id      BIGINT        NOT NULL REFERENCES contrato_laboral(id) ON DELETE CASCADE,
    concepto_codigo  VARCHAR(30)   NOT NULL,
    cantidad         NUMERIC(10,2),
    valor            NUMERIC(18,2),
    porcentaje       NUMERIC(7,4),
    fecha_desde      DATE          NOT NULL,
    fecha_hasta      DATE,
    observacion      VARCHAR(300),
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP,
    deleted_at       TIMESTAMP,
    CONSTRAINT ck_vinc_concepto_fechas CHECK (fecha_hasta IS NULL OR fecha_hasta >= fecha_desde)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_vinc_concepto_contrato ON vinculacion_concepto (contrato_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS vinculacion_novedad_permitida (
    id                     BIGSERIAL     PRIMARY KEY,
    contrato_id            BIGINT        NOT NULL REFERENCES contrato_laboral(id) ON DELETE CASCADE,
    concepto_codigo        VARCHAR(30)   NOT NULL,
    tope_cantidad          NUMERIC(10,2),
    tope_valor             NUMERIC(18,2),
    requiere_autorizacion  BOOLEAN       NOT NULL DEFAULT FALSE,
    created_at             TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_vinc_novedad_concepto
    ON vinculacion_novedad_permitida (contrato_id, concepto_codigo)
MIG_SQL);
    }

    public function down(): void
    {
        // Sin reversa: los datos migrados no se deshacen.
    }
};
