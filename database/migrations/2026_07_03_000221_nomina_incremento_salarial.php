<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V221 — Incremento salarial y retroactivos (rediseño de nómina R8).
 *
 * Traducción de la migración Flyway V221__nomina_incremento_salarial.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nomina_incremento (
    id                 BIGSERIAL     PRIMARY KEY,
    empresa_id         INTEGER       NOT NULL REFERENCES empresa(id),
    numero             INTEGER       NOT NULL,
    tipo_lista         VARCHAR(20)   NOT NULL,
    lista_ids          TEXT,
    tipo_incremento    VARCHAR(20)   NOT NULL,
    valor              NUMERIC(15,4),
    redondeo           VARCHAR(20)   NOT NULL DEFAULT 'NINGUNO',
    fecha_aplicacion   DATE          NOT NULL,
    descripcion        VARCHAR(300),
    estado             VARCHAR(12)   NOT NULL DEFAULT 'PENDIENTE',
    documento_id       BIGINT        REFERENCES nomina_documento(id),
    usuario_id         BIGINT,
    aplicado_at        TIMESTAMP,
    reversado_at       TIMESTAMP,
    created_at         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP,
    CONSTRAINT ck_nomina_incremento_tipo CHECK (tipo_incremento IN ('PORCENTAJE', 'VALOR_ADICIONAL', 'NUEVO_VALOR', 'SMMLV')),
    CONSTRAINT ck_nomina_incremento_redondeo CHECK (redondeo IN ('NINGUNO', 'PESO', 'CENTENA', 'CENTENA_ARRIBA', 'CENTENA_ABAJO', 'MIL')),
    CONSTRAINT ck_nomina_incremento_estado CHECK (estado IN ('PENDIENTE', 'APLICADO', 'REVERSADO', 'ANULADO')),
    CONSTRAINT uq_nomina_incremento_numero UNIQUE (empresa_id, numero)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nomina_incremento_detalle (
    id                 BIGSERIAL     PRIMARY KEY,
    incremento_id      BIGINT        NOT NULL REFERENCES nomina_incremento(id) ON DELETE CASCADE,
    contrato_id        BIGINT        NOT NULL REFERENCES contrato_laboral(id),
    empleado_id        BIGINT        NOT NULL REFERENCES empleados(id),
    salario_anterior   NUMERIC(15,2) NOT NULL,
    salario_nuevo      NUMERIC(15,2) NOT NULL,
    historial_id       BIGINT,
    CONSTRAINT uq_nomina_incremento_detalle UNIQUE (incremento_id, contrato_id)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_nomina_incremento_empresa ON nomina_incremento (empresa_id, fecha_aplicacion DESC)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_nomina_incremento_detalle_contrato ON nomina_incremento_detalle (contrato_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO concepto_nomina (empresa_id, codigo, nombre, clase, constituye_ibc, base, vigente_desde, codigo_dian,
       orden, activo, created_at)
SELECT NULL, 'RETROACTIVO', 'Retroactivo salarial', 'DEVENGADO', TRUE, 'NOVEDAD', DATE '2020-01-01', 'OtrosConceptos',
       53, TRUE, now()
 WHERE NOT EXISTS (SELECT 1 FROM concepto_nomina c WHERE c.empresa_id IS NULL AND c.codigo = 'RETROACTIVO')
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement("DELETE FROM concepto_nomina WHERE empresa_id IS NULL AND codigo = 'RETROACTIVO'");
        DB::statement('DROP TABLE IF EXISTS nomina_incremento_detalle');
        DB::statement('DROP TABLE IF EXISTS nomina_incremento');
    }
};
