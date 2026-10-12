<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V214 — Tipos de comprobante de nómina (rediseño R3).
 *
 * Traducción de la migración Flyway V214__nomina_tipo_comprobante.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nomina_tipo_comprobante (
    id           BIGSERIAL     PRIMARY KEY,
    empresa_id   INTEGER       NOT NULL REFERENCES empresa(id),
    codigo       VARCHAR(20)   NOT NULL,
    nombre       VARCHAR(150)  NOT NULL,
    clase        VARCHAR(30)   NOT NULL,
    prefijo      VARCHAR(10)   NOT NULL,
    frecuencia   VARCHAR(15),
    descripcion  VARCHAR(500),
    activo       BOOLEAN       NOT NULL DEFAULT TRUE,
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP,
    deleted_at   TIMESTAMP,
    CONSTRAINT ck_nomina_tipo_comprobante_clase CHECK (clase IN ('PERIODICO', 'PRIMA', 'CESANTIAS',
        'INTERESES_CESANTIAS', 'LIQUIDACION_DEFINITIVA', 'RETROACTIVO', 'OTROS_PAGOS')),
    CONSTRAINT ck_nomina_tipo_comprobante_frecuencia CHECK (frecuencia IS NULL
        OR frecuencia IN ('MENSUAL', 'QUINCENAL', 'SEMANAL'))
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_nomina_tipo_comprobante_codigo
    ON nomina_tipo_comprobante (empresa_id, codigo) WHERE deleted_at IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_nomina_tipo_comprobante_prefijo
    ON nomina_tipo_comprobante (empresa_id, prefijo) WHERE deleted_at IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nomina_tipo_comprobante_vinculacion (
    id                   BIGSERIAL  PRIMARY KEY,
    tipo_comprobante_id  BIGINT     NOT NULL REFERENCES nomina_tipo_comprobante(id) ON DELETE CASCADE,
    tipo_vinculacion_id  BIGINT     NOT NULL REFERENCES nomina_tipo_vinculacion(id)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_nomina_tipo_comprobante_vinculacion
    ON nomina_tipo_comprobante_vinculacion (tipo_comprobante_id, tipo_vinculacion_id)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS nomina_tipo_comprobante_vinculacion');
        DB::statement('DROP TABLE IF EXISTS nomina_tipo_comprobante');
    }
};
