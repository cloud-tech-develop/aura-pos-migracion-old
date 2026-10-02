<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V183 — Bodega principal para las sucursales que quedaron sin ella.
 *
 * V172 le dio "Bodega Principal" solo a las sucursales que existían al correrla.
 * Crear una sucursal (o una empresa nueva) no creaba su bodega, y esa sede no
 * podía vender, comprar ni registrar mermas. Desde ahora el backend la crea al
 * crear la sucursal; esto repara las que se crearon en el intermedio.
 *
 * Traducción de la migración Flyway V183__bodega_principal_faltante.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
INSERT INTO bodega (empresa_id, sucursal_id, codigo, nombre, es_principal, permite_venta, activa)
SELECT s.empresa_id, s.id, 'BOD-' || s.id, 'Bodega Principal', TRUE, TRUE, TRUE
  FROM sucursal s
 WHERE NOT EXISTS (SELECT 1 FROM bodega b WHERE b.sucursal_id = s.id AND b.es_principal)
   AND NOT EXISTS (SELECT 1 FROM bodega b
                    WHERE b.sucursal_id = s.id AND LOWER(b.nombre) = 'bodega principal')
   AND NOT EXISTS (SELECT 1 FROM bodega b
                    WHERE b.empresa_id = s.empresa_id AND UPPER(b.codigo) = 'BOD-' || s.id)
MIG_SQL);

        // Si ya había una "Bodega Principal" sin marcar como principal, se marca.
        DB::statement(<<<'MIG_SQL'
UPDATE bodega b SET es_principal = TRUE
 WHERE LOWER(b.nombre) = 'bodega principal'
   AND NOT EXISTS (SELECT 1 FROM bodega p WHERE p.sucursal_id = b.sucursal_id AND p.es_principal)
MIG_SQL);
    }

    public function down(): void
    {
        // Nada que deshacer: quitar la bodega principal dejaría la sucursal sin poder operar.
    }
};
