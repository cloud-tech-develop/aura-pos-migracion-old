<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V200 — Firma contable, segunda etapa: clientes gestionados, cupo y solicitudes.
 *
 * Traducción de la migración Flyway V200__firma_gestionados_solicitudes.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE empresa_firma_cliente
    ADD COLUMN IF NOT EXISTS modalidad VARCHAR(20) NOT NULL DEFAULT 'CLIENTE_PAGA'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS cupo_gestionados INT NOT NULL DEFAULT 10
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS solicitud_firma_cliente (
    id                  BIGSERIAL     PRIMARY KEY,
    cliente_empresa_id  INT           NOT NULL REFERENCES empresa(id),
    firma_empresa_id    INT           NOT NULL REFERENCES empresa(id),
    estado              VARCHAR(15)   NOT NULL DEFAULT 'PENDIENTE',
    solicitado_por      INT,
    solicitado_at       TIMESTAMP     NOT NULL DEFAULT now(),
    respondido_por      INT,
    respondido_at       TIMESTAMP,
    motivo              VARCHAR(300),
    CONSTRAINT ck_solicitud_firma_distintas CHECK (firma_empresa_id <> cliente_empresa_id)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_solicitud_firma_pendiente
    ON solicitud_firma_cliente (cliente_empresa_id) WHERE estado = 'PENDIENTE'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_solicitud_firma_firma
    ON solicitud_firma_cliente (firma_empresa_id, estado)
MIG_SQL);
    }

    public function down(): void
    {
        // Sin vuelta atrás automática.
    }
};
