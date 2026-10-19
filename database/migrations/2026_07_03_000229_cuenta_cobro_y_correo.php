<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V229 — Cuenta de cobro (factura_venta.tipo, declaración art. 383 ET) y registro
 * de los correos que envía Aura (correo_envio).
 *
 * Traducción de la migración Flyway V229__cuenta_cobro_y_correo.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE factura_venta ADD COLUMN IF NOT EXISTS tipo VARCHAR(15) NOT NULL DEFAULT 'FACTURA'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE factura_venta ADD COLUMN IF NOT EXISTS contrato_dos_trabajadores BOOLEAN
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS correo_envio (
    id               BIGSERIAL     PRIMARY KEY,
    empresa_id       INT           NOT NULL REFERENCES empresa(id),
    lote_id          VARCHAR(36),
    tipo             VARCHAR(20)   NOT NULL,
    documento_id     BIGINT        NOT NULL,
    destinatario     VARCHAR(500)  NOT NULL,
    cc               VARCHAR(500),
    asunto           VARCHAR(200)  NOT NULL,
    mensaje          TEXT,
    adjunto_nombre   VARCHAR(150),
    adjunto          BYTEA,
    estado           VARCHAR(12)   NOT NULL DEFAULT 'PENDIENTE',
    intentos         INT           NOT NULL DEFAULT 0,
    proveedor_id     VARCHAR(80),
    error            VARCHAR(500),
    usuario_id       INT,
    created_at       TIMESTAMP     NOT NULL DEFAULT now(),
    enviado_at       TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_correo_envio_documento ON correo_envio (empresa_id, tipo, documento_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_correo_envio_lote ON correo_envio (lote_id) WHERE lote_id IS NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_correo_envio_pendiente ON correo_envio (id) WHERE estado = 'PENDIENTE'
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS correo_envio');
        DB::statement('ALTER TABLE factura_venta DROP COLUMN IF EXISTS contrato_dos_trabajadores');
        DB::statement('ALTER TABLE factura_venta DROP COLUMN IF EXISTS tipo');
    }
};
