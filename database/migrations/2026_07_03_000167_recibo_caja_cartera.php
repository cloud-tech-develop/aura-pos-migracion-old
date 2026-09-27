<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V167 — recibo de caja multi-factura.
 * 
 * Un pago del cliente se reparte entre varias cuentas por cobrar. Por dentro
 * cada aplicación sigue siendo un abonos_cobrar (arqueo, asiento RC y reportes
 * no cambian); recibo_caja agrupa y numera, y recibo_caja_aplicacion conserva
 * qué se aplicó aunque la anulación borre los abonos. El sobrante queda como
 * anticipo del cliente (2805), que ahora guarda la cuenta donde entró la plata.
 *
 * Traducción de la migración Flyway V167__recibo_caja_cartera.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS recibo_caja (
    id                  BIGSERIAL     PRIMARY KEY,
    empresa_id          INTEGER       NOT NULL REFERENCES empresa(id),
    tercero_id          BIGINT        NOT NULL REFERENCES tercero(id),
    numero              VARCHAR(30)   NOT NULL,
    consecutivo         INTEGER       NOT NULL,
    fecha_pago          TIMESTAMP     NOT NULL,
    valor_recibido      NUMERIC(15,2) NOT NULL,
    valor_aplicado      NUMERIC(15,2) NOT NULL DEFAULT 0,
    valor_anticipo      NUMERIC(15,2) NOT NULL DEFAULT 0,
    metodo_pago         VARCHAR(30)   NOT NULL,
    referencia          VARCHAR(255),
    cuenta_contable_id  BIGINT,
    turno_caja_id       BIGINT        REFERENCES turno_caja(id),
    caja_otro_dia       BOOLEAN       NOT NULL DEFAULT FALSE,
    anticipo_id         BIGINT        REFERENCES anticipo(id),
    observaciones       VARCHAR(500),
    estado              VARCHAR(12)   NOT NULL DEFAULT 'ACTIVO',
    motivo_anulacion    VARCHAR(300),
    usuario_id          INTEGER       REFERENCES usuario(id),
    anulado_por         INTEGER       REFERENCES usuario(id),
    anulado_at          TIMESTAMP,
    created_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_recibo_caja_numero UNIQUE (empresa_id, consecutivo),
    CONSTRAINT ck_recibo_caja_estado CHECK (estado IN ('ACTIVO','ANULADO'))
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_recibo_caja_tercero ON recibo_caja (empresa_id, tercero_id, fecha_pago DESC)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS recibo_caja_aplicacion (
    id                BIGSERIAL     PRIMARY KEY,
    recibo_caja_id    BIGINT        NOT NULL REFERENCES recibo_caja(id),
    cuenta_cobrar_id  BIGINT        NOT NULL REFERENCES cuentas_cobrar(id),
    abono_cobrar_id   BIGINT,
    monto             NUMERIC(15,2) NOT NULL,
    saldo_anterior    NUMERIC(15,2) NOT NULL,
    created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_recibo_caja_aplicacion_recibo ON recibo_caja_aplicacion (recibo_caja_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_recibo_caja_aplicacion_cuenta ON recibo_caja_aplicacion (cuenta_cobrar_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE abonos_cobrar ADD COLUMN IF NOT EXISTS recibo_caja_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE anticipo ADD COLUMN IF NOT EXISTS cuenta_contable_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE anticipo ADD COLUMN IF NOT EXISTS recibo_caja_id BIGINT
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE anticipo DROP COLUMN IF EXISTS recibo_caja_id');
        DB::statement('ALTER TABLE anticipo DROP COLUMN IF EXISTS cuenta_contable_id');
        DB::statement('ALTER TABLE abonos_cobrar DROP COLUMN IF EXISTS recibo_caja_id');
        DB::statement('DROP TABLE IF EXISTS recibo_caja_aplicacion');
        DB::statement('DROP TABLE IF EXISTS recibo_caja');
    }
};
