<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V228 — Activación de los documentos electrónicos de un cliente gestionado: la
 * firma la solicita y la plataforma pone las credenciales de Factus.
 *
 * Traducción de la migración Flyway V228__activacion_electronica_gestionado.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS solicitud_activacion_electronica (
    id                  BIGSERIAL     PRIMARY KEY,
    cliente_empresa_id  INT           NOT NULL REFERENCES empresa(id),
    firma_empresa_id    INT           NOT NULL REFERENCES empresa(id),
    servicios           VARCHAR(120)  NOT NULL,
    observacion         VARCHAR(300),
    estado              VARCHAR(15)   NOT NULL DEFAULT 'PENDIENTE',
    solicitado_por      INT,
    solicitado_at       TIMESTAMP     NOT NULL DEFAULT now(),
    respondido_por      INT,
    respondido_at       TIMESTAMP,
    motivo              VARCHAR(300)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_solicitud_activacion_pendiente
    ON solicitud_activacion_electronica (cliente_empresa_id) WHERE estado = 'PENDIENTE'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_solicitud_activacion_firma
    ON solicitud_activacion_electronica (firma_empresa_id, estado)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS solicitud_activacion_electronica');
    }
};
