<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V202 — Conciliación fiscal, formatos 2516 y 2517: cabecera, balance cargado y ajustes.
 *
 * Traducción de la migración Flyway V202__conciliacion_fiscal.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS conciliacion_fiscal (
    id              BIGSERIAL      PRIMARY KEY,
    empresa_id      INT            NOT NULL REFERENCES empresa(id),
    anio            INT            NOT NULL,
    formato         VARCHAR(4)     NOT NULL,
    tarifa_renta    NUMERIC(5,2)   NOT NULL DEFAULT 35,
    origen          VARCHAR(10),
    cargado_at      TIMESTAMP,
    estado          VARCHAR(10)    NOT NULL DEFAULT 'BORRADOR',
    usuario_id      INT,
    created_at      TIMESTAMP      NOT NULL DEFAULT now(),
    updated_at      TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_conciliacion_fiscal_anio
    ON conciliacion_fiscal (empresa_id, anio, formato)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS conciliacion_fiscal_cuenta (
    id               BIGSERIAL      PRIMARY KEY,
    conciliacion_id  BIGINT         NOT NULL REFERENCES conciliacion_fiscal(id) ON DELETE CASCADE,
    codigo           VARCHAR(20)    NOT NULL,
    nombre           VARCHAR(200),
    saldo            NUMERIC(20,2)  NOT NULL DEFAULT 0,
    renglon          VARCHAR(10),
    renglon_manual   BOOLEAN        NOT NULL DEFAULT false
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_conciliacion_fiscal_cuenta
    ON conciliacion_fiscal_cuenta (conciliacion_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS conciliacion_fiscal_ajuste (
    id               BIGSERIAL      PRIMARY KEY,
    conciliacion_id  BIGINT         NOT NULL REFERENCES conciliacion_fiscal(id) ON DELETE CASCADE,
    renglon          VARCHAR(10)    NOT NULL,
    tipo             VARCHAR(6)     NOT NULL,
    diferencia       VARCHAR(12)    NOT NULL,
    valor            NUMERIC(20,2)  NOT NULL,
    concepto         VARCHAR(300)   NOT NULL,
    cuenta_codigo    VARCHAR(20),
    sugerencia       VARCHAR(60),
    created_at       TIMESTAMP      NOT NULL DEFAULT now()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_conciliacion_fiscal_ajuste
    ON conciliacion_fiscal_ajuste (conciliacion_id)
MIG_SQL);
    }

    public function down(): void
    {
        // Sin vuelta atrás automática.
    }
};
