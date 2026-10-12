<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V205 — Cartera: política de intereses de mora, intereses facturados, castigo de cartera (C5 y C9).
 *
 * Traducción de la migración Flyway V205__cartera_intereses_mora_castigo.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS cartera_politica (
    empresa_id              INTEGER       PRIMARY KEY REFERENCES empresa(id),
    interes_activo          BOOLEAN       NOT NULL DEFAULT FALSE,
    tasa_mora_mensual       NUMERIC(7,4)  NOT NULL DEFAULT 0,
    tasa_usura_mensual      NUMERIC(7,4)  NOT NULL DEFAULT 0,
    usura_vigente_hasta     DATE,
    dias_gracia             INTEGER       NOT NULL DEFAULT 0,
    deterioro_tramos        TEXT,
    dias_sugerir_castigo    INTEGER       NOT NULL DEFAULT 360,
    usuario_id              INTEGER       REFERENCES usuario(id),
    updated_at              TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS interes_mora (
    id                 BIGSERIAL     PRIMARY KEY,
    empresa_id         INTEGER       NOT NULL REFERENCES empresa(id),
    tercero_id         BIGINT        NOT NULL REFERENCES tercero(id),
    numero             VARCHAR(30)   NOT NULL,
    consecutivo        INTEGER       NOT NULL,
    fecha              DATE          NOT NULL,
    tasa_mensual       NUMERIC(7,4)  NOT NULL,
    valor              NUMERIC(15,2) NOT NULL,
    cuenta_cobrar_id   BIGINT        REFERENCES cuentas_cobrar(id),
    estado             VARCHAR(12)   NOT NULL DEFAULT 'FACTURADO',
    observaciones      VARCHAR(500),
    motivo_anulacion   VARCHAR(300),
    usuario_id         INTEGER       REFERENCES usuario(id),
    anulado_por        INTEGER       REFERENCES usuario(id),
    anulado_at         TIMESTAMP,
    created_at         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_interes_mora_numero UNIQUE (empresa_id, consecutivo),
    CONSTRAINT ck_interes_mora_estado CHECK (estado IN ('FACTURADO','ANULADO'))
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_interes_mora_tercero ON interes_mora (empresa_id, tercero_id, fecha DESC)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS interes_mora_detalle (
    id                 BIGSERIAL     PRIMARY KEY,
    interes_mora_id    BIGINT        NOT NULL REFERENCES interes_mora(id),
    cuenta_cobrar_id   BIGINT        NOT NULL REFERENCES cuentas_cobrar(id),
    desde              DATE          NOT NULL,
    hasta              DATE          NOT NULL,
    dias               INTEGER       NOT NULL,
    saldo_base         NUMERIC(15,2) NOT NULL,
    valor              NUMERIC(15,2) NOT NULL
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_interes_mora_detalle_cuenta ON interes_mora_detalle (cuenta_cobrar_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS cartera_castigo (
    id                 BIGSERIAL     PRIMARY KEY,
    empresa_id         INTEGER       NOT NULL REFERENCES empresa(id),
    cuenta_cobrar_id   BIGINT        NOT NULL REFERENCES cuentas_cobrar(id),
    tercero_id         BIGINT        NOT NULL REFERENCES tercero(id),
    numero             VARCHAR(30)   NOT NULL,
    consecutivo        INTEGER       NOT NULL,
    fecha              DATE          NOT NULL,
    valor              NUMERIC(15,2) NOT NULL,
    contra_provision   NUMERIC(15,2) NOT NULL DEFAULT 0,
    contra_gasto       NUMERIC(15,2) NOT NULL DEFAULT 0,
    dias_mora          INTEGER       NOT NULL DEFAULT 0,
    motivo             VARCHAR(500)  NOT NULL,
    estado             VARCHAR(12)   NOT NULL DEFAULT 'CASTIGADO',
    estado_anterior    VARCHAR(20),
    motivo_anulacion   VARCHAR(300),
    usuario_id         INTEGER       REFERENCES usuario(id),
    anulado_por        INTEGER       REFERENCES usuario(id),
    anulado_at         TIMESTAMP,
    created_at         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_cartera_castigo_numero UNIQUE (empresa_id, consecutivo),
    CONSTRAINT ck_cartera_castigo_estado CHECK (estado IN ('CASTIGADO','ANULADO'))
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_cartera_castigo_empresa ON cartera_castigo (empresa_id, fecha DESC)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_cartera_castigo_cuenta_vivo
    ON cartera_castigo (cuenta_cobrar_id) WHERE estado = 'CASTIGADO'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE cuentas_cobrar ADD COLUMN IF NOT EXISTS valor_castigado NUMERIC(15,2) NOT NULL DEFAULT 0
MIG_SQL);
    }

    public function down(): void
    {
        // Aditiva: no se revierte.
    }
};
