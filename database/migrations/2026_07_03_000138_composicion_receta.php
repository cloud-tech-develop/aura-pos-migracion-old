<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V138 — composición como RECETA (rendimiento por lote + unidad de consumo).
 *
 * Traducción fiel de la migración Flyway V138__composicion_receta.sql
 * (aura-back-old): cada sentencia de PostgreSQL va en su propio DB::statement.
 * Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Decimales reales ─────────────────────────────────────────────
        DB::statement(<<<'MIG_SQL'
-- Problema que resuelve: hoy `producto_composicion.cantidad` es "cuánto del
-- hijo entra en 1 unidad del padre, expresado en la unidad BASE de stock del
-- hijo". Una panadería que compra harina por BULTO tendría que escribir
-- 0.005 bulto por pan. Nadie hace eso, y encima el panadero no piensa por pan:
-- piensa "con esta masa saco 40 panes".
--
-- Solución: se guarda lo que el usuario ESCRIBE (cantidad_receta, en su unidad,
-- por lote) y se DERIVA `cantidad` con la fórmula:
--
--     cantidad = (cantidad_receta * factor_unidad / (1 - merma)) / rendimiento
--
-- `cantidad` conserva exactamente su semántica anterior, así que la venta
-- (validación de stock y descuento de inventario) NO se toca.
--
-- 10 kg de harina repartidos en 400 panes = 0.025 kg. Con NUMERIC(x,2) eso se
-- redondea a 0.03 (+20% de consumo) o a 0.00 (no descuenta nada). El resto del
-- motor de ventas ya calcula con scale 6; la columna tenía que acompañar.

ALTER TABLE producto_composicion
    ALTER COLUMN cantidad TYPE NUMERIC(18, 6)
MIG_SQL);

        // ── 2. La receta tal como la escribe el usuario ──────────────────────
        DB::statement(<<<'MIG_SQL'
ALTER TABLE producto_composicion
    -- Cantidad POR LOTE en la unidad elegida por el usuario (ej: 10 = 10 kg
    -- de harina para el lote completo). Es el campo editable; `cantidad` se
    -- recalcula a partir de este en cada guardado.
    ADD COLUMN IF NOT EXISTS cantidad_receta NUMERIC(18, 6),

    -- Unidad en la que el usuario escribió `cantidad_receta`. Informativa para
    -- la UI: el cálculo lo hace `factor_unidad`.
    ADD COLUMN IF NOT EXISTS unidad_medida_id BIGINT,

    -- Presentación del hijo de la que se dedujo el factor (ej: "bulto 50 kg").
    -- Si viene, el backend deriva `factor_unidad` de ella y la deja trazada.
    ADD COLUMN IF NOT EXISTS producto_presentacion_id BIGINT,

    -- Cuántas unidades BASE de stock del hijo equivale 1 unidad de las escritas.
    -- Ej: stock de harina en bultos de 50 kg, receta en kg → factor = 0.02.
    ADD COLUMN IF NOT EXISTS factor_unidad NUMERIC(18, 6) NOT NULL DEFAULT 1,

    -- Merma del ingrediente en el proceso (evaporación, recorte, cáscara).
    -- 0..99.99 en porcentaje. Se consume más de lo que queda en el producto.
    ADD COLUMN IF NOT EXISTS merma_porcentaje NUMERIC(7, 4) NOT NULL DEFAULT 0,

    ADD COLUMN IF NOT EXISTS orden INTEGER,
    ADD COLUMN IF NOT EXISTS nota  VARCHAR(300)
MIG_SQL);

        // FKs sin ON DELETE CASCADE a propósito: borrar una unidad de medida o
        // una presentación no debe borrar silenciosamente una línea de receta.
        DB::statement(<<<'MIG_SQL'
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_composicion_unidad_medida') THEN
        ALTER TABLE producto_composicion
            ADD CONSTRAINT fk_composicion_unidad_medida
            FOREIGN KEY (unidad_medida_id) REFERENCES unidad_medida(id);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_composicion_presentacion') THEN
        ALTER TABLE producto_composicion
            ADD CONSTRAINT fk_composicion_presentacion
            FOREIGN KEY (producto_presentacion_id) REFERENCES producto_presentacion(id);
    END IF;
END $$
MIG_SQL);

        // ── 3. Rendimiento del lote, en el producto padre ────────────────────
        DB::statement(<<<'MIG_SQL'
-- Vive en `producto` y no en una cabecera nueva porque hoy hay exactamente una
-- receta por producto padre (la unicidad padre+hijo lo garantiza). Una tabla
-- `producto_receta` solo se justifica el día que existan versiones de receta.
ALTER TABLE producto
    ADD COLUMN IF NOT EXISTS rendimiento_receta NUMERIC(18, 6) NOT NULL DEFAULT 1
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN producto.rendimiento_receta IS
    'Unidades del producto que salen de un lote de su receta (ej: 40 panes). Default 1 = la receta se escribe por unidad.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN producto_composicion.cantidad IS
    'DERIVADA — consumo en unidad base del hijo por 1 unidad del padre. La usa el descuento de inventario en venta.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN producto_composicion.cantidad_receta IS
    'Cantidad por LOTE en la unidad que eligió el usuario. Campo editable de la UI.'
MIG_SQL);

        // ── 4. Backfill de las recetas existentes ────────────────────────────
        DB::statement(<<<'MIG_SQL'
-- Rendimiento 1 + factor 1 ⇒ cantidad_receta = cantidad. Las recetas viejas
-- siguen calculando idéntico.
UPDATE producto_composicion
   SET cantidad_receta = cantidad
 WHERE cantidad_receta IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- La unidad mostrada por defecto es la unidad base de stock del hijo, que es
-- justamente en la que estaban expresadas esas cantidades.
UPDATE producto_composicion pc
   SET unidad_medida_id = p.unidad_medida_base_id
  FROM producto p
 WHERE p.id = pc.producto_hijo_id
   AND pc.unidad_medida_id IS NULL
MIG_SQL);

        // ── 5. Índices ───────────────────────────────────────────────────────
        // `findByProductoPadreId` se ejecuta 4 veces por línea de venta.
        DB::statement('CREATE INDEX IF NOT EXISTS idx_composicion_padre ON producto_composicion(producto_padre_id)');

        // El explosionado inverso ("¿en qué recetas entra esta harina?") y la
        // validación de ciclos recorren por hijo.
        DB::statement('CREATE INDEX IF NOT EXISTS idx_composicion_hijo  ON producto_composicion(producto_hijo_id)');

        // Un mismo hijo no puede estar dos veces en la misma receta: el guardado
        // por lote hace diff contra esta llave.
        //
        // El servicio ya validaba la unicidad, pero la BD no: si alguna fila
        // duplicada se coló por otra vía, el CREATE UNIQUE INDEX tumbaría el
        // arranque. Se consolidan sumando (dos filas del mismo ingrediente = el
        // total que entra) y se conserva la de menor id.
        DB::statement(<<<'MIG_SQL'
WITH duplicadas AS (
    SELECT producto_padre_id,
           producto_hijo_id,
           MIN(id)           AS id_conservar,
           SUM(cantidad)     AS cantidad_total
      FROM producto_composicion
     GROUP BY producto_padre_id, producto_hijo_id
    HAVING COUNT(*) > 1
)
UPDATE producto_composicion pc
   SET cantidad        = d.cantidad_total,
       cantidad_receta = d.cantidad_total
  FROM duplicadas d
 WHERE pc.id = d.id_conservar
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
DELETE FROM producto_composicion pc
 WHERE EXISTS (
    SELECT 1
      FROM producto_composicion otra
     WHERE otra.producto_padre_id = pc.producto_padre_id
       AND otra.producto_hijo_id  = pc.producto_hijo_id
       AND otra.id                < pc.id
 )
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_composicion_padre_hijo
    ON producto_composicion(producto_padre_id, producto_hijo_id)
MIG_SQL);
    }

    public function down(): void
    {
        // Migración de datos/columnas (ver up): la consolidación de recetas
        // duplicadas no es reversible. Reversa no automática.
    }
};
