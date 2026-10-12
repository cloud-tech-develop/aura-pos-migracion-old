<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V219 — Retiros (rediseño de nómina R5).
 *
 * Traducción de la migración Flyway V219__nomina_retiro.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nomina_retiro (
    id                     BIGSERIAL     PRIMARY KEY,
    empresa_id             INTEGER       NOT NULL REFERENCES empresa(id),
    contrato_id            BIGINT        NOT NULL REFERENCES contrato_laboral(id),
    empleado_id            BIGINT        NOT NULL REFERENCES empleados(id),
    fecha_retiro           DATE          NOT NULL,
    motivo_id              BIGINT        NOT NULL REFERENCES nomina_catalogo(id),
    indemniza              BOOLEAN       NOT NULL DEFAULT FALSE,
    fecha_fin_pactada      DATE,
    cesantias_corte_hasta  DATE,
    observacion            VARCHAR(500),
    estado                 VARCHAR(12)   NOT NULL DEFAULT 'VIGENTE',
    documento_id           BIGINT        REFERENCES nomina_documento(id),
    usuario_id             BIGINT,
    created_at             TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP,
    anulado_at             TIMESTAMP,
    CONSTRAINT ck_nomina_retiro_estado CHECK (estado IN ('VIGENTE', 'ANULADO'))
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_nomina_retiro_contrato_vigente
    ON nomina_retiro (contrato_id) WHERE estado = 'VIGENTE'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_nomina_retiro_empresa_fecha
    ON nomina_retiro (empresa_id, fecha_retiro DESC)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS nomina_retiro');
    }
};
