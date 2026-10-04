<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V195 — Facturación ERP fuera del POS (FV0/FV1 de docs/PLAN_FACTURACION.md).
 *
 * Traducción de la migración Flyway V195__facturacion_erp.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 *
 * condicion_pago (Contado/30/60/90 por empresa), factura_venta y su detalle
 * (borrador y datos ERP de la factura emitida en venta), venta_detalle.descripcion
 * y el submódulo Ventas › Facturas.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS condicion_pago (
    id          BIGSERIAL    PRIMARY KEY,
    empresa_id  INT          NOT NULL,
    nombre      VARCHAR(80)  NOT NULL,
    dias        INT          NOT NULL DEFAULT 0,
    activa      BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP    NOT NULL DEFAULT now(),
    updated_at  TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_condicion_pago_empresa_nombre
    ON condicion_pago (empresa_id, nombre)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO condicion_pago (empresa_id, nombre, dias)
SELECT e.id, v.nombre, v.dias
FROM empresa e
CROSS JOIN (VALUES ('Contado', 0), ('Crédito 30 días', 30), ('Crédito 60 días', 60), ('Crédito 90 días', 90))
    AS v(nombre, dias)
ON CONFLICT (empresa_id, nombre) DO NOTHING
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS factura_venta (
    id                    BIGSERIAL      PRIMARY KEY,
    empresa_id            INT            NOT NULL,
    sucursal_id           INT            NOT NULL,
    bodega_id             BIGINT,
    cliente_id            BIGINT         NOT NULL,
    vendedor_id           INT,
    condicion_pago_id     BIGINT         REFERENCES condicion_pago(id),
    forma_pago            VARCHAR(10)    NOT NULL DEFAULT 'CREDITO',
    metodo_pago           VARCHAR(30),
    cuenta_bancaria_id    BIGINT,
    fecha_vencimiento     DATE,
    orden_compra          VARCHAR(60),
    notas                 VARCHAR(1000),
    estado                VARCHAR(10)    NOT NULL DEFAULT 'BORRADOR',
    venta_id              BIGINT,
    subtotal              NUMERIC(15,2)  NOT NULL DEFAULT 0,
    descuento_total       NUMERIC(15,2)  NOT NULL DEFAULT 0,
    impuestos_total       NUMERIC(15,2)  NOT NULL DEFAULT 0,
    total                 NUMERIC(15,2)  NOT NULL DEFAULT 0,
    usuario_id            INT,
    emitida_por           INT,
    emitida_at            TIMESTAMP,
    created_at            TIMESTAMP      NOT NULL DEFAULT now(),
    updated_at            TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_factura_venta_empresa ON factura_venta (empresa_id, estado)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_factura_venta_venta ON factura_venta (venta_id) WHERE venta_id IS NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS factura_venta_detalle (
    id                        BIGSERIAL      PRIMARY KEY,
    factura_venta_id          BIGINT         NOT NULL REFERENCES factura_venta(id) ON DELETE CASCADE,
    producto_id               BIGINT         NOT NULL,
    producto_presentacion_id  BIGINT,
    descripcion               VARCHAR(500),
    cantidad                  NUMERIC(18,6)  NOT NULL,
    precio_unitario           NUMERIC(15,2)  NOT NULL,
    descuento_valor           NUMERIC(15,2)  NOT NULL DEFAULT 0,
    impuesto_porcentaje       NUMERIC(6,2)   NOT NULL DEFAULT 0,
    impuesto_valor            NUMERIC(15,2)  NOT NULL DEFAULT 0,
    subtotal_linea            NUMERIC(15,2)  NOT NULL DEFAULT 0,
    orden                     INT            NOT NULL DEFAULT 0
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_factura_venta_detalle_factura ON factura_venta_detalle (factura_venta_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE venta_detalle ADD COLUMN IF NOT EXISTS descripcion VARCHAR(500)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO submodulos (modulo_id, nombre, codigo, descripcion, activo, orden, created_at, updated_at)
SELECT m.id, 'Facturas', 'facturas',
       'Factura de venta fuera del punto de venta: borrador, crédito o contado, productos y servicios',
       TRUE, 0, now(), now()
FROM modulos m
WHERE m.codigo = 'ventas'
  AND NOT EXISTS (SELECT 1 FROM submodulos s WHERE s.modulo_id = m.id AND s.codigo = 'facturas')
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO empresa_submodulo (empresa_id, submodulo_id, activo, created_at, updated_at)
SELECT em.empresa_id, s.id, TRUE, now(), now()
FROM submodulos s
JOIN modulos m ON m.id = s.modulo_id AND m.codigo = 'ventas'
JOIN empresa_modulo em ON em.modulo_id = m.id AND em.activo = TRUE
WHERE s.codigo = 'facturas'
  AND NOT EXISTS (
      SELECT 1 FROM empresa_submodulo es
      WHERE es.empresa_id = em.empresa_id AND es.submodulo_id = s.id
  )
MIG_SQL);
    }

    public function down(): void
    {
        // Sin vuelta atrás automática: las facturas emitidas viven en venta.
    }
};
