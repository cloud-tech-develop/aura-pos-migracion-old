<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V203 — Retenciones de la compra por concepto: la compra guarda la tarifa de
 * cada retención y cada empresa recibe el catálogo inicial de tarifas con la
 * cuenta de su concepto (honorarios → 236515, servicios → 236525…).
 *
 * Traducción de la migración Flyway V203__retenciones_compra_por_concepto.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE compra ADD COLUMN IF NOT EXISTS retefuente_tarifa_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE compra ADD COLUMN IF NOT EXISTS reteiva_tarifa_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE compra ADD COLUMN IF NOT EXISTS reteica_tarifa_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO tarifa_retencion (empresa_id, tipo, concepto, tarifa_natural, tarifa_juridica,
                              base_minima, cuenta_contable_id, activo, created_at)
SELECT e.id, c.tipo, c.concepto, c.pct_natural, c.pct_juridica, 0,
       (SELECT pc.id
          FROM plan_cuenta pc
         WHERE pc.empresa_id = e.id
           AND pc.codigo = ANY (c.cuentas)
           AND pc.auxiliar AND pc.activa
         ORDER BY array_position(c.cuentas, pc.codigo::text)
         LIMIT 1),
       true, now()
  FROM empresa e
 CROSS JOIN (VALUES
    ('RETEFUENTE', 'Compras generales (declarantes)',          2.5,  2.5, ARRAY['23654001','236540','2365']),
    ('RETEFUENTE', 'Compras generales (no declarantes)',       3.5,  3.5, ARRAY['23654001','236540','2365']),
    ('RETEFUENTE', 'Servicios generales (declarantes)',        4.0,  4.0, ARRAY['23652501','236525','2365']),
    ('RETEFUENTE', 'Servicios generales (no declarantes)',     6.0,  6.0, ARRAY['23652502','236525','2365']),
    ('RETEFUENTE', 'Honorarios',                              11.0, 11.0, ARRAY['23651502','236515','2365']),
    ('RETEFUENTE', 'Honorarios (no declarantes)',             10.0, 10.0, ARRAY['23651501','236515','2365']),
    ('RETEFUENTE', 'Comisiones',                              11.0, 11.0, ARRAY['236520','23651502','2365']),
    ('RETEFUENTE', 'Comisiones (no declarantes)',             10.0, 10.0, ARRAY['236520','23651501','2365']),
    ('RETEFUENTE', 'Arrendamiento de bienes inmuebles',        3.5,  3.5, ARRAY['23653001','236530','2365']),
    ('RETEFUENTE', 'Arrendamiento de bienes muebles',          4.0,  4.0, ARRAY['23653001','236530','2365']),
    ('RETEFUENTE', 'Transporte de carga',                      1.0,  1.0, ARRAY['23652501','236525','2365']),
    ('RETEFUENTE', 'Transporte de pasajeros',                  3.5,  3.5, ARRAY['23652501','236525','2365']),
    ('RETEFUENTE', 'Servicios temporales (sobre AIU)',         1.0,  1.0, ARRAY['23652501','236525','2365']),
    ('RETEFUENTE', 'Aseo y vigilancia (sobre AIU)',            2.0,  2.0, ARRAY['23652501','236525','2365']),
    ('RETEFUENTE', 'Hoteles y restaurantes',                   3.5,  3.5, ARRAY['23652501','236525','2365']),
    ('RETEFUENTE', 'Contratos de construcción',                2.0,  2.0, ARRAY['23652501','236525','2365']),
    ('RETEIVA',    'ReteIVA general (15% del IVA)',           15.0, 15.0, ARRAY['23670501','236705','2367']),
    ('RETEICA',    'ReteICA actividades comerciales',          1.0,  1.0, ARRAY['23680501','236805','2368']),
    ('RETEICA',    'ReteICA actividades de servicios',         1.0,  1.0, ARRAY['23680501','236805','2368']),
    ('RETEICA',    'ReteICA actividades industriales',         0.7,  0.7, ARRAY['23680501','236805','2368'])
 ) AS c(tipo, concepto, pct_natural, pct_juridica, cuentas)
ON CONFLICT (empresa_id, tipo, concepto) DO NOTHING
MIG_SQL);
    }

    public function down(): void
    {
        // Aditiva: no se revierte (las compras ya pueden apuntar a sus tarifas).
    }
};
