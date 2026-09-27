<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V163 — consumo interno (el negocio usa su propio inventario).
 *
 * Traducción de la migración Flyway V163__consumo_interno.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 *
 * Crea los conceptos por empresa (con su cuenta de gasto y el IVA por
 * defecto), el documento y su detalle. Amplía el origen de
 * inventario_consumo_componente y agrega el último consumo interno a
 * producto_cambio_unidad.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS concepto_consumo_interno (
    id          BIGSERIAL    PRIMARY KEY,
    empresa_id  INTEGER      NOT NULL,
    nombre      VARCHAR(80)  NOT NULL,
    cuenta_id   BIGINT,
    genera_iva  BOOLEAN      NOT NULL DEFAULT TRUE,
    activo      BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_concepto_consumo_empresa FOREIGN KEY (empresa_id) REFERENCES empresa(id),
    CONSTRAINT fk_concepto_consumo_cuenta  FOREIGN KEY (cuenta_id)  REFERENCES plan_cuenta(id)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_concepto_consumo_nombre
    ON concepto_consumo_interno (empresa_id, LOWER(nombre))
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS consumo_interno (
    id                     BIGSERIAL     PRIMARY KEY,
    empresa_id             INTEGER       NOT NULL,
    sucursal_id            INTEGER       NOT NULL,
    usuario_id             INTEGER,
    concepto_id            BIGINT        NOT NULL,
    responsable_tercero_id BIGINT,
    fecha                  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacion            VARCHAR(300),
    costo_total            NUMERIC(15,2) NOT NULL DEFAULT 0,
    base_comercial_total   NUMERIC(15,2) NOT NULL DEFAULT 0,
    iva_total              NUMERIC(15,2) NOT NULL DEFAULT 0,
    genera_iva             BOOLEAN       NOT NULL DEFAULT TRUE,
    estado                 VARCHAR(20)   NOT NULL DEFAULT 'APROBADO',
    created_at             TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_consumo_interno_estado CHECK (estado IN ('APROBADO', 'ANULADO')),
    CONSTRAINT fk_consumo_interno_empresa     FOREIGN KEY (empresa_id)  REFERENCES empresa(id),
    CONSTRAINT fk_consumo_interno_sucursal    FOREIGN KEY (sucursal_id) REFERENCES sucursal(id),
    CONSTRAINT fk_consumo_interno_concepto    FOREIGN KEY (concepto_id) REFERENCES concepto_consumo_interno(id),
    CONSTRAINT fk_consumo_interno_responsable FOREIGN KEY (responsable_tercero_id) REFERENCES tercero(id)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS consumo_interno_detalle (
    id                       BIGSERIAL     PRIMARY KEY,
    consumo_interno_id       BIGINT        NOT NULL,
    producto_id              BIGINT        NOT NULL,
    lote_id                  BIGINT,
    producto_presentacion_id BIGINT,
    cantidad_presentacion    NUMERIC(18,6),
    cantidad                 NUMERIC(18,6) NOT NULL,
    costo_unitario           NUMERIC(15,2) NOT NULL DEFAULT 0,
    base_comercial_unitaria  NUMERIC(15,2) NOT NULL DEFAULT 0,
    iva_valor                NUMERIC(15,2) NOT NULL DEFAULT 0,

    CONSTRAINT fk_consumo_detalle_consumo      FOREIGN KEY (consumo_interno_id)       REFERENCES consumo_interno(id),
    CONSTRAINT fk_consumo_detalle_producto     FOREIGN KEY (producto_id)              REFERENCES producto(id),
    CONSTRAINT fk_consumo_detalle_lote         FOREIGN KEY (lote_id)                  REFERENCES lote(id),
    CONSTRAINT fk_consumo_detalle_presentacion FOREIGN KEY (producto_presentacion_id) REFERENCES producto_presentacion(id)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_consumo_interno_empresa ON consumo_interno (empresa_id, fecha)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_consumo_interno_detalle ON consumo_interno_detalle (consumo_interno_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE inventario_consumo_componente
    DROP CONSTRAINT IF EXISTS chk_consumo_componente_origen
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE inventario_consumo_componente
    ADD CONSTRAINT chk_consumo_componente_origen
    CHECK (origen IN ('MERMA', 'OBSEQUIO', 'VENTA', 'CONSUMO_INTERNO'))
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE producto_cambio_unidad
    ADD COLUMN IF NOT EXISTS ultimo_consumo_interno_id BIGINT NOT NULL DEFAULT 0
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO concepto_consumo_interno (empresa_id, nombre, genera_iva)
SELECT e.id, v.nombre, TRUE
  FROM empresa e
 CROSS JOIN (VALUES
        ('Aseo y cafetería'),
        ('Papelería y útiles'),
        ('Mantenimiento del local'),
        ('Dotación y consumo del personal'),
        ('Exhibición y decoración'),
        ('Otro')
  ) AS v(nombre)
 WHERE NOT EXISTS (
        SELECT 1 FROM concepto_consumo_interno c WHERE c.empresa_id = e.id)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE producto_cambio_unidad DROP COLUMN IF EXISTS ultimo_consumo_interno_id');
        DB::statement('ALTER TABLE inventario_consumo_componente DROP CONSTRAINT IF EXISTS chk_consumo_componente_origen');
        DB::statement(<<<'MIG_SQL'
ALTER TABLE inventario_consumo_componente
    ADD CONSTRAINT chk_consumo_componente_origen
    CHECK (origen IN ('MERMA', 'OBSEQUIO', 'VENTA'))
MIG_SQL);
        DB::statement('DROP TABLE IF EXISTS consumo_interno_detalle');
        DB::statement('DROP TABLE IF EXISTS consumo_interno');
        DB::statement('DROP TABLE IF EXISTS concepto_consumo_interno');
    }
};
