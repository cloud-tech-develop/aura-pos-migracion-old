<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V231 — Cargo como plantilla de nómina: conceptos, novedades permitidas, topes de
 * horas extra y recargos, adjuntos, perfil y desempeño (docs/PLAN_CONCEPTOS_CARGOS_NOMINA.md, F5).
 * 
 * Traducción de la migración Flyway V231__cargo_plantilla_nomina.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE nomina_cargo ADD COLUMN IF NOT EXISTS perfil TEXT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE nomina_cargo ADD COLUMN IF NOT EXISTS desempeno TEXT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nomina_cargo_concepto (
    id               BIGSERIAL     PRIMARY KEY,
    cargo_id         BIGINT        NOT NULL REFERENCES nomina_cargo(id) ON DELETE CASCADE,
    concepto_numero  INT           NOT NULL,
    cantidad         NUMERIC(10,2),
    valor            NUMERIC(18,2),
    porcentaje       NUMERIC(7,4),
    observacion      VARCHAR(300),
    created_at       TIMESTAMP     NOT NULL DEFAULT now(),
    updated_at       TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_cargo_concepto ON nomina_cargo_concepto (cargo_id, concepto_numero)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nomina_cargo_novedad (
    id                     BIGSERIAL     PRIMARY KEY,
    cargo_id               BIGINT        NOT NULL REFERENCES nomina_cargo(id) ON DELETE CASCADE,
    concepto_numero        INT           NOT NULL,
    tope_cantidad          NUMERIC(10,2),
    tope_valor             NUMERIC(18,2),
    requiere_autorizacion  BOOLEAN       NOT NULL DEFAULT FALSE,
    created_at             TIMESTAMP     NOT NULL DEFAULT now(),
    updated_at             TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_cargo_novedad ON nomina_cargo_novedad (cargo_id, concepto_numero)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nomina_cargo_tope_horas (
    id                BIGSERIAL     PRIMARY KEY,
    cargo_id          BIGINT        NOT NULL REFERENCES nomina_cargo(id) ON DELETE CASCADE,
    tipo_concepto_id  INT           NOT NULL REFERENCES nomina_concepto_tipo(id),
    tope_dia          NUMERIC(5,2),
    tope_semana       NUMERIC(5,2),
    tope_mes          NUMERIC(6,2),
    created_at        TIMESTAMP     NOT NULL DEFAULT now(),
    updated_at        TIMESTAMP,
    CONSTRAINT ck_cargo_tope_horas_tipo CHECK (tipo_concepto_id BETWEEN 3 AND 9)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_cargo_tope_horas ON nomina_cargo_tope_horas (cargo_id, tipo_concepto_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nomina_cargo_adjunto (
    id              BIGSERIAL     PRIMARY KEY,
    empresa_id      INT           NOT NULL REFERENCES empresa(id),
    cargo_id        BIGINT        NOT NULL REFERENCES nomina_cargo(id) ON DELETE CASCADE,
    nombre_archivo  VARCHAR(255)  NOT NULL,
    archivo_url     VARCHAR(600)  NOT NULL,
    content_type    VARCHAR(120),
    tamano_bytes    BIGINT,
    usuario_id      INT,
    created_at      TIMESTAMP     NOT NULL DEFAULT now(),
    deleted_at      TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_cargo_adjunto_cargo ON nomina_cargo_adjunto (cargo_id)
MIG_SQL);
    }

    public function down(): void
    {
        // Sin reversa: migración aditiva sobre datos en uso.
    }
};
