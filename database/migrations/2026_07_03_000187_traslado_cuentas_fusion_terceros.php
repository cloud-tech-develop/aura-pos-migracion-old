<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V187 — traslado de cuentas y fusión de terceros (Fase 4).
 *
 * Bitácoras de las dos herramientas del contador: mover los movimientos de una
 * cuenta a otra en un rango de fechas y dejar un solo tercero cuando hay duplicados.
 *
 * Traducción de la migración Flyway V187__traslado_cuentas_fusion_terceros.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS traslado_cuenta_log (
    id                BIGSERIAL    PRIMARY KEY,
    empresa_id        INT          NOT NULL,
    cuenta_origen_id  BIGINT       NOT NULL,
    cuenta_destino_id BIGINT       NOT NULL,
    desde             DATE         NOT NULL,
    hasta             DATE         NOT NULL,
    tercero_id        BIGINT,
    lineas_movidas    INT          NOT NULL,
    motivo            VARCHAR(300) NOT NULL,
    usuario_id        BIGINT,
    created_at        TIMESTAMP    NOT NULL DEFAULT now()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS ix_traslado_cuenta_log_empresa ON traslado_cuenta_log (empresa_id, created_at DESC)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS fusion_tercero_log (
    id                 BIGSERIAL    PRIMARY KEY,
    empresa_id         INT          NOT NULL,
    tercero_origen_id  BIGINT       NOT NULL,
    tercero_destino_id BIGINT       NOT NULL,
    origen_documento   VARCHAR(40),
    origen_nombre      VARCHAR(250),
    detalle            TEXT,
    registros_movidos  INT          NOT NULL,
    motivo             VARCHAR(300) NOT NULL,
    usuario_id         BIGINT,
    created_at         TIMESTAMP    NOT NULL DEFAULT now()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS ix_fusion_tercero_log_empresa ON fusion_tercero_log (empresa_id, created_at DESC)
MIG_SQL);
    }

    public function down(): void
    {
        // Aditiva: no se revierte en bases con datos.
    }
};
