<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V155 — índice para el reporte de movimiento de inventario.
 *
 * Traducción de la migración Flyway V155__kardex_reporte_indice.sql
 * (aura-back-old).
 *
 * Hasta ahora movimiento_inventario solo se consultaba con "los últimos N
 * movimientos", que el índice de la llave primaria resuelve barriendo por id
 * descendente. El reporte pregunta otra cosa: qué se movió de este producto, en
 * esta sucursal, entre estas dos fechas — y eso, sin índice, es un scan completo
 * de la tabla que más filas acumula del sistema.
 *
 * El orden de las columnas sigue la selectividad de los filtros del reporte: la
 * sucursal casi siempre viene fija, el producto es opcional y la fecha acota el
 * rango.
 *
 * Solo agrega índices: no cambia el esquema, así que el backend arranca igual
 * sin ella. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mov_inv_reporte ON movimiento_inventario (sucursal_id, producto_id, created_at)');

        // El reporte agrupado también filtra por tipo (una lista de ellos)
        // sobre el rango de fechas ya acotado. Un índice aparte por tipo evita
        // releer las filas descartadas cuando se pide, por ejemplo, "todas las
        // anulaciones del mes".
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mov_inv_tipo_fecha ON movimiento_inventario (tipo_movimiento, created_at)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_mov_inv_tipo_fecha');
        DB::statement('DROP INDEX IF EXISTS idx_mov_inv_reporte');
    }
};
