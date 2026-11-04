<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V235 — Costo de las ventas viejas que no lo tienen.
 *
 * Traducción de la migración Flyway V235__venta_detalle_costo_retroactivo.sql (aura-back-old).
 * A cada venta_detalle con costo_linea en null le pone el costo que tenía el
 * producto en la fecha de la venta (historial de costos → última compra → costo
 * previo al historial → costo actual), sumando componentes si tiene receta. Las
 * devoluciones de esas líneas reciben su parte. Cada sentencia en su propio
 * DB::statement. Idempotente: solo toca filas en null; las funciones de apoyo se
 * borran al final.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE OR REPLACE FUNCTION aura_v235_costo_unitario(p_producto BIGINT, p_fecha TIMESTAMP)
RETURNS NUMERIC LANGUAGE sql STABLE AS $$
    SELECT COALESCE(
        (SELECT h.costo_nuevo
           FROM producto_costo_historial h
          WHERE h.producto_id = p_producto AND h.fecha <= p_fecha
          ORDER BY h.fecha DESC, h.id DESC LIMIT 1),
        (SELECT cd.subtotal_linea / cd.cantidad
           FROM compra_detalle cd
           JOIN compra c ON c.id = cd.compra_id
          WHERE cd.producto_id = p_producto
            AND c.estado = 'RECIBIDA'
            AND c.fecha <= p_fecha
            AND cd.cantidad > 0 AND cd.subtotal_linea > 0
          ORDER BY c.fecha DESC, cd.id DESC LIMIT 1),
        (SELECT h.costo_anterior
           FROM producto_costo_historial h
          WHERE h.producto_id = p_producto AND h.costo_anterior > 0
          ORDER BY h.fecha, h.id LIMIT 1),
        (SELECT NULLIF(p.costo, 0) FROM producto p WHERE p.id = p_producto)
    )
$$
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE OR REPLACE FUNCTION aura_v235_costo_linea(p_producto BIGINT, p_cantidad_base NUMERIC, p_fecha TIMESTAMP)
RETURNS NUMERIC LANGUAGE sql STABLE AS $$
    SELECT CASE
        WHEN EXISTS (SELECT 1 FROM producto_composicion pc WHERE pc.producto_padre_id = p_producto)
            THEN (SELECT SUM(p_cantidad_base * pc.cantidad * aura_v235_costo_unitario(pc.producto_hijo_id::BIGINT, p_fecha))
                    FROM producto_composicion pc
                   WHERE pc.producto_padre_id = p_producto)
        ELSE p_cantidad_base * aura_v235_costo_unitario(p_producto, p_fecha)
    END
$$
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE devolucion_detalle dd
   SET costo_linea = ROUND(aura_v235_costo_linea(
           dd.producto_id::BIGINT,
           (dd.cantidad * COALESCE((SELECT NULLIF(pp.factor_conversion, 0)
                                      FROM producto_presentacion pp
                                     WHERE pp.id = dd.producto_presentacion_id), 1))::NUMERIC,
           v.fecha_emision::TIMESTAMP), 2)
  FROM devolucion d
  JOIN venta v ON v.id = d.venta_id
  JOIN venta_detalle vd ON vd.venta_id = d.venta_id
 WHERE dd.devolucion_id = d.id
   AND vd.id = (SELECT MIN(x.id) FROM venta_detalle x
                 WHERE x.venta_id = d.venta_id AND x.producto_id = dd.producto_id)
   AND COALESCE(d.estado, '') <> 'ANULADA'
   AND dd.costo_linea IS NULL
   AND vd.costo_linea IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE venta_detalle vd
   SET costo_linea = ROUND(aura_v235_costo_linea(
           vd.producto_id::BIGINT,
           (vd.cantidad * COALESCE((SELECT NULLIF(pp.factor_conversion, 0)
                                      FROM producto_presentacion pp
                                     WHERE pp.id = vd.producto_presentacion_id), 1))::NUMERIC,
           v.fecha_emision::TIMESTAMP), 2)
  FROM venta v
 WHERE v.id = vd.venta_id
   AND vd.costo_linea IS NULL
   AND vd.producto_id IS NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
DROP FUNCTION IF EXISTS aura_v235_costo_linea(BIGINT, NUMERIC, TIMESTAMP)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
DROP FUNCTION IF EXISTS aura_v235_costo_unitario(BIGINT, TIMESTAMP)
MIG_SQL);
    }

    public function down(): void
    {
        // Sin reversa: el costo llenado es el dato correcto de la venta.
    }
};
