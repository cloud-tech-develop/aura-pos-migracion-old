<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V185 — catálogo unificado (Fase 2 del plan World Office).
 *
 * Todo lo que la empresa compra se clasifica: PRODUCTO, SERVICIO, GASTO, DOTACION,
 * ACTIVO_FIJO, INTANGIBLE o DIFERIDO. Solo PRODUCTO mueve inventario; ACTIVO_FIJO e
 * INTANGIBLE crean la ficha del activo al comprarse y DIFERIDO crea el diferido con
 * sus cuotas. Además: unidades sueltas en la línea de compra, máximo y punto de
 * reorden por bodega, y las cuentas del PUC que usan las clasificaciones nuevas.
 *
 * Traducción de la migración Flyway V185__catalogo_unificado.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE producto ADD COLUMN IF NOT EXISTS clasificacion VARCHAR(20) NOT NULL DEFAULT 'PRODUCTO'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE producto p SET clasificacion = 'SERVICIO',
       maneja_inventario = FALSE, maneja_lotes = FALSE, maneja_serial = FALSE
WHERE p.tipo_producto = 'SERVICIO' AND p.clasificacion = 'PRODUCTO'
  AND NOT EXISTS (SELECT 1 FROM inventario i WHERE i.producto_id = p.id AND i.stock_actual <> 0)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_producto_clasificacion') THEN
        ALTER TABLE producto ADD CONSTRAINT ck_producto_clasificacion CHECK (clasificacion IN
            ('PRODUCTO','SERVICIO','GASTO','DOTACION','ACTIVO_FIJO','INTANGIBLE','DIFERIDO'));
    END IF;
END $$
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE compra_detalle ADD COLUMN IF NOT EXISTS clasificacion VARCHAR(20)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE compra_detalle ADD COLUMN IF NOT EXISTS cantidad_suelta NUMERIC(38,6)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE categoria_contable_producto ADD COLUMN IF NOT EXISTS cuenta_depreciacion_id       BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE categoria_contable_producto ADD COLUMN IF NOT EXISTS cuenta_gasto_depreciacion_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE categoria_contable_producto ADD COLUMN IF NOT EXISTS vida_util_meses              INTEGER
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE categoria_contable_producto ADD COLUMN IF NOT EXISTS meses_diferido               INTEGER
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE activo_fijo ADD COLUMN IF NOT EXISTS compra_id         BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE activo_fijo ADD COLUMN IF NOT EXISTS compra_detalle_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE activo_fijo ADD COLUMN IF NOT EXISTS producto_id       BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS ix_activo_fijo_compra ON activo_fijo (compra_id) WHERE compra_id IS NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS diferido (
    id                 BIGSERIAL     PRIMARY KEY,
    empresa_id         INT           NOT NULL,
    origen_tipo        VARCHAR(20)   NOT NULL,              -- COMPRA
    origen_id          BIGINT        NOT NULL,
    compra_detalle_id  BIGINT,
    producto_id        BIGINT,
    descripcion        VARCHAR(200)  NOT NULL,
    monto              NUMERIC(18,2) NOT NULL,
    meses              INT           NOT NULL,
    fecha_inicio       DATE          NOT NULL,
    cuenta_gasto_id    BIGINT,
    cuenta_diferido_id BIGINT,
    tercero_id         BIGINT,
    centro_costo_id    BIGINT,
    estado             VARCHAR(20)   NOT NULL DEFAULT 'VIGENTE', -- VIGENTE | TERMINADO | ANULADO
    created_at         TIMESTAMP     NOT NULL DEFAULT now()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS ix_diferido_origen ON diferido (empresa_id, origen_tipo, origen_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE diferido_amortizacion ADD COLUMN IF NOT EXISTS diferido_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE diferido_amortizacion ALTER COLUMN gasto_id DROP NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS ux_diferido_amortizacion_periodo
    ON diferido_amortizacion (diferido_id, periodo) WHERE diferido_id IS NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE inventario ADD COLUMN IF NOT EXISTS stock_maximo  NUMERIC(38,6)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE inventario ADD COLUMN IF NOT EXISTS punto_reorden NUMERIC(38,6)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
SELECT c.empresa_id, '16', 'Intangibles', 'ACTIVO', 'DEBITO', 2, c.id, TRUE, FALSE, now()
FROM plan_cuenta c WHERE c.codigo = '1'
  AND NOT EXISTS (SELECT 1 FROM plan_cuenta x WHERE x.empresa_id = c.empresa_id AND x.codigo = '16')
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
SELECT p.empresa_id, v.codigo, v.nombre, v.tipo, v.naturaleza, 3, p.id, TRUE, TRUE, now()
FROM (VALUES ('1524', 'Equipo de Oficina',                        'ACTIVO', 'DEBITO',  '15'),
             ('1528', 'Equipo de Computacion y Comunicacion',     'ACTIVO', 'DEBITO',  '15'),
             ('1635', 'Licencias',                                'ACTIVO', 'DEBITO',  '16'),
             ('1698', 'Amortizacion Acumulada',                   'ACTIVO', 'CREDITO', '16'),
             ('4245', 'Utilidad en Venta de Propiedades Planta y Equipo', 'INGRESO', 'CREDITO', '42'),
             ('5310', 'Perdida en Venta y Retiro de Bienes',      'GASTO',  'DEBITO',  '53')
     ) AS v(codigo, nombre, tipo, naturaleza, padre)
JOIN plan_cuenta p ON p.codigo = v.padre
WHERE NOT EXISTS (SELECT 1 FROM plan_cuenta x
                  WHERE x.empresa_id = p.empresa_id AND x.codigo = v.codigo)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
SELECT p.empresa_id, '510551', 'Dotacion y Suministro a Trabajadores', 'GASTO', 'DEBITO', 4, p.id, TRUE, TRUE, now()
FROM plan_cuenta p WHERE p.codigo = '5105'
  AND NOT EXISTS (SELECT 1 FROM plan_cuenta x WHERE x.empresa_id = p.empresa_id AND x.codigo = '510551')
MIG_SQL);
    }

    public function down(): void
    {
        // Aditiva: no se revierte en bases con datos.
    }
};
