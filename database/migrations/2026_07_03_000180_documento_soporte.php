<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V180 — Documento soporte electrónico (compras y gastos a no obligados a facturar).
 *
 * Traducción de la migración Flyway V180__documento_soporte.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS documento_soporte (
    id                 BIGSERIAL PRIMARY KEY,
    empresa_id         INT          NOT NULL,
    origen_tipo        VARCHAR(20)  NOT NULL,
    origen_id          BIGINT       NOT NULL,
    tercero_id         BIGINT,
    reference_code     VARCHAR(60)  NOT NULL,
    numbering_range_id VARCHAR(20),
    numero             VARCHAR(40),
    cude               VARCHAR(200),
    estado             VARCHAR(20)  NOT NULL,
    total              NUMERIC(18,2),
    retenciones        NUMERIC(18,2),
    payload_json       TEXT,
    response_json      TEXT,
    mensaje_error      TEXT,
    usuario_id         INT,
    created_at         TIMESTAMP    NOT NULL DEFAULT now(),
    updated_at         TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS ux_documento_soporte_reference
    ON documento_soporte (empresa_id, reference_code)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS ux_documento_soporte_origen_aceptado
    ON documento_soporte (empresa_id, origen_tipo, origen_id)
    WHERE estado = 'ACEPTADO'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_documento_soporte_empresa_fecha
    ON documento_soporte (empresa_id, created_at)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS documento_soporte');
    }
};
