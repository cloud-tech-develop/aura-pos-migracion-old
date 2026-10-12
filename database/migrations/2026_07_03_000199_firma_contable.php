<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V199 — Firma contable (docs/PLAN_FIRMA_CONTABLE.md, F1).
 *
 * Traducción de la migración Flyway V199__firma_contable.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS es_firma_contable BOOLEAN NOT NULL DEFAULT FALSE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS empresa_firma_cliente (
    id                  BIGSERIAL  PRIMARY KEY,
    firma_empresa_id    INT        NOT NULL REFERENCES empresa(id),
    cliente_empresa_id  INT        NOT NULL REFERENCES empresa(id),
    perfil_id           BIGINT     REFERENCES perfil(id),
    activo              BOOLEAN    NOT NULL DEFAULT TRUE,
    creado_por          INT,
    created_at          TIMESTAMP  NOT NULL DEFAULT now(),
    revocado_por        INT,
    revocado_at         TIMESTAMP,
    CONSTRAINT ck_firma_cliente_distintas CHECK (firma_empresa_id <> cliente_empresa_id)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_firma_cliente_activo
    ON empresa_firma_cliente (cliente_empresa_id) WHERE activo
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_firma_cliente_firma
    ON empresa_firma_cliente (firma_empresa_id) WHERE activo
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS usuario_firma_cliente (
    id          BIGSERIAL  PRIMARY KEY,
    usuario_id  INT        NOT NULL REFERENCES usuario(id) ON DELETE CASCADE,
    vinculo_id  BIGINT     NOT NULL REFERENCES empresa_firma_cliente(id) ON DELETE CASCADE,
    activo      BOOLEAN    NOT NULL DEFAULT TRUE,
    created_by  INT,
    created_at  TIMESTAMP  NOT NULL DEFAULT now(),
    updated_at  TIMESTAMP  NOT NULL DEFAULT now()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_usuario_firma_cliente
    ON usuario_firma_cliente (usuario_id, vinculo_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_usuario_firma_cliente_usuario
    ON usuario_firma_cliente (usuario_id) WHERE activo
MIG_SQL);
    }

    public function down(): void
    {
        // Sin vuelta atrás automática.
    }
};
