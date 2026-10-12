<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V201 — Causación de gastos y servicios: cabecera y líneas.
 *
 * Traducción de la migración Flyway V201__causacion_gasto.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS causacion_gasto (
    id                   BIGSERIAL      PRIMARY KEY,
    empresa_id           INT            NOT NULL REFERENCES empresa(id),
    sucursal_id          INT            REFERENCES sucursal(id),
    consecutivo          INT            NOT NULL,
    tercero_id           BIGINT         NOT NULL REFERENCES tercero(id),
    fecha                DATE           NOT NULL,
    fecha_vencimiento    DATE,
    tipo_soporte         VARCHAR(20)    NOT NULL DEFAULT 'FACTURA',
    numero_soporte       VARCHAR(50),
    descripcion          VARCHAR(500),
    cuenta_pagar_contable_id BIGINT,
    subtotal             NUMERIC(18,2)  NOT NULL DEFAULT 0,
    iva                  NUMERIC(18,2)  NOT NULL DEFAULT 0,
    retefuente           NUMERIC(18,2)  NOT NULL DEFAULT 0,
    reteiva              NUMERIC(18,2)  NOT NULL DEFAULT 0,
    reteica              NUMERIC(18,2)  NOT NULL DEFAULT 0,
    total_pagar          NUMERIC(18,2)  NOT NULL DEFAULT 0,
    cuenta_pagar_id      BIGINT,
    estado               VARCHAR(15)    NOT NULL DEFAULT 'CAUSADA',
    usuario_id           INT,
    anulado_por          INT,
    anulado_at           TIMESTAMP,
    motivo_anulacion     VARCHAR(300),
    created_at           TIMESTAMP      NOT NULL DEFAULT now(),
    updated_at           TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_causacion_gasto_consecutivo
    ON causacion_gasto (empresa_id, consecutivo)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_causacion_gasto_tercero
    ON causacion_gasto (empresa_id, tercero_id, fecha)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS causacion_gasto_linea (
    id                   BIGSERIAL      PRIMARY KEY,
    causacion_id         BIGINT         NOT NULL REFERENCES causacion_gasto(id) ON DELETE CASCADE,
    cuenta_id            BIGINT         NOT NULL,
    descripcion          VARCHAR(300),
    centro_costo_id      BIGINT,
    base                 NUMERIC(18,2)  NOT NULL,
    tarifa_iva           NUMERIC(7,4)   NOT NULL DEFAULT 0,
    valor_iva            NUMERIC(18,2)  NOT NULL DEFAULT 0,
    iva_descontable      BOOLEAN        NOT NULL DEFAULT true,
    tarifa_retencion_id  BIGINT,
    tarifa_retefuente    NUMERIC(7,4)   NOT NULL DEFAULT 0,
    valor_retefuente     NUMERIC(18,2)  NOT NULL DEFAULT 0,
    tarifa_reteiva       NUMERIC(7,4)   NOT NULL DEFAULT 0,
    valor_reteiva        NUMERIC(18,2)  NOT NULL DEFAULT 0,
    tarifa_reteica       NUMERIC(7,4)   NOT NULL DEFAULT 0,
    valor_reteica        NUMERIC(18,2)  NOT NULL DEFAULT 0
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_causacion_gasto_linea_causacion
    ON causacion_gasto_linea (causacion_id)
MIG_SQL);
    }

    public function down(): void
    {
        // Sin vuelta atrás automática.
    }
};
