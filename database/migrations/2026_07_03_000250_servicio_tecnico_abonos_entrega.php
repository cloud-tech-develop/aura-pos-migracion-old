<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V250 — Servicio técnico: abonos, entrega con factura y enlace público.
 *
 * Traducción de la migración Flyway V250__servicio_tecnico_abonos_entrega.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE servicio_tecnico_config ADD COLUMN IF NOT EXISTS producto_servicio_id BIGINT REFERENCES producto(id)');
        DB::statement('ALTER TABLE servicio_orden ADD COLUMN IF NOT EXISTS factura_venta_id BIGINT');
        DB::statement('ALTER TABLE servicio_orden ADD COLUMN IF NOT EXISTS token_publico VARCHAR(40)');
        DB::statement('ALTER TABLE servicio_orden ADD COLUMN IF NOT EXISTS aprobado_cliente_at TIMESTAMP');
        DB::statement('UPDATE servicio_orden
   SET token_publico = md5(random()::text || clock_timestamp()::text || id::text)
 WHERE token_publico IS NULL');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_servicio_orden_token ON servicio_orden (token_publico)');
        DB::statement('CREATE TABLE IF NOT EXISTS servicio_orden_abono (
    id              BIGSERIAL     PRIMARY KEY,
    empresa_id      INT           NOT NULL,
    orden_id        BIGINT        NOT NULL,
    recibo_caja_id  BIGINT        NOT NULL,
    anticipo_id     BIGINT,
    monto           NUMERIC(18,2) NOT NULL CHECK (monto > 0),
    metodo_pago     VARCHAR(40)   NOT NULL,
    estado          VARCHAR(10)   NOT NULL DEFAULT \'ACTIVO\' CHECK (estado IN (\'ACTIVO\', \'ANULADO\')),
    usuario_id      BIGINT,
    created_at      TIMESTAMP     NOT NULL DEFAULT now(),
    CONSTRAINT fk_servicio_abono_orden FOREIGN KEY (orden_id, empresa_id)
        REFERENCES servicio_orden (id, empresa_id) ON DELETE CASCADE
)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_servicio_abono_orden ON servicio_orden_abono (orden_id)');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS servicio_orden_abono');
    }
};
