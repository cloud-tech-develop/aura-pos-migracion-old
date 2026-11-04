<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V234 — La devolución también le quita el costo a la línea de la venta.
 *
 * Traducción de la migración Flyway V234__devolucion_costo_linea.sql (aura-back-old).
 * Agrega devolucion_detalle.costo_linea (costo que la devolución le quitó a
 * venta_detalle, para devolverlo al anular) y corrige las devoluciones ya hechas:
 * antes la línea quedaba con menos venta y todo el costo, y la utilidad bajaba.
 * Cada sentencia en su propio DB::statement. Idempotente: la corrección solo toca
 * devoluciones con costo_linea en null.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE devolucion_detalle ADD COLUMN IF NOT EXISTS costo_linea NUMERIC(38,2)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
WITH dev AS (
    SELECT dd.id AS dd_id,
           dd.cantidad,
           dd.costo_linea,
           (SELECT MIN(vd.id) FROM venta_detalle vd
             WHERE vd.venta_id = d.venta_id AND vd.producto_id = dd.producto_id) AS vd_id
    FROM devolucion_detalle dd
    JOIN devolucion d ON d.id = dd.devolucion_id
    WHERE d.venta_id IS NOT NULL
      AND COALESCE(d.estado, '') <> 'ANULADA'
),
por_linea AS (
    -- Cantidad original = lo que queda + todo lo devuelto (vigente).
    -- Costo original = lo que queda + lo que ya se quitó con la regla nueva.
    SELECT vd.id AS vd_id,
           (vd.costo_linea + SUM(COALESCE(dev.costo_linea, 0)))
               / NULLIF(vd.cantidad + SUM(dev.cantidad), 0) AS costo_unit
    FROM dev
    JOIN venta_detalle vd ON vd.id = dev.vd_id
    WHERE vd.costo_linea IS NOT NULL
    GROUP BY vd.id, vd.cantidad, vd.costo_linea
    HAVING COUNT(*) FILTER (WHERE dev.costo_linea IS NULL) > 0
),
asignado AS (
    UPDATE devolucion_detalle dd
       SET costo_linea = ROUND(pl.costo_unit * dev.cantidad, 2)
      FROM dev
      JOIN por_linea pl ON pl.vd_id = dev.vd_id
     WHERE dd.id = dev.dd_id
       AND dd.costo_linea IS NULL
       AND pl.costo_unit IS NOT NULL
    RETURNING dev.vd_id, dd.costo_linea
)
UPDATE venta_detalle vd
   SET costo_linea = GREATEST(0, vd.costo_linea - a.total)
  FROM (SELECT vd_id, SUM(costo_linea) AS total FROM asignado GROUP BY vd_id) a
 WHERE vd.id = a.vd_id
MIG_SQL);
    }

    public function down(): void
    {
        // Sin reversa: la corrección de costos no se deshace.
    }
};
