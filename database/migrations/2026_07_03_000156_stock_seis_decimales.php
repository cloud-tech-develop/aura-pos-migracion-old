<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V156 — el stock y el kardex guardan 6 decimales.
 *
 * Traducción de la migración Flyway V156__stock_seis_decimales.sql
 * (aura-back-old).
 *
 * Vender una presentación divide la cantidad por su factor: 1 leche de una paca
 * x6 son 1/6 = 0.166667 pacas. Con 2 decimales se guardaba 0.17, así que cada
 * leche suelta descontaba de más (6 leches = 1.02 pacas) y cada venta de varias
 * descontaba de menos (5 leches = 0.83). El inventario se desviaba del conteo
 * físico con cualquier factor que no divida a 100 (3, 6, 8, 12, 24…).
 *
 * 6 decimales es la misma escala con la que VentaServiceImpl hace la división y
 * con la que producto_composicion ya guarda el consumo de cada componente.
 *
 * Ampliar la escala no pierde datos. Idempotente: solo altera las columnas que
 * aún tienen menos de 6 decimales, así no reescribe tablas grandes de nuevo.
 */
return new class extends Migration
{
    private const COLUMNAS = [
        ['inventario', 'stock_actual'],
        ['lote', 'stock_actual'],
        ['movimiento_inventario', 'cantidad'],
        ['movimiento_inventario', 'saldo_anterior'],
        ['movimiento_inventario', 'saldo_nuevo'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNAS as [$tabla, $columna]) {
            $escala = DB::selectOne(<<<'MIG_SQL'
                SELECT numeric_scale
                FROM information_schema.columns
                WHERE table_schema = current_schema()
                  AND table_name = ?
                  AND column_name = ?
            MIG_SQL, [$tabla, $columna]);

            if ($escala === null || ($escala->numeric_scale !== null && (int) $escala->numeric_scale >= 6)) {
                continue;
            }

            DB::statement("ALTER TABLE {$tabla} ALTER COLUMN {$columna} TYPE NUMERIC(38,6)");
        }
    }

    public function down(): void
    {
        // Volver a 2 decimales redondea el stock guardado: solo en una base de
        // prueba, nunca con ventas por presentación ya registradas.
        foreach (self::COLUMNAS as [$tabla, $columna]) {
            DB::statement("ALTER TABLE {$tabla} ALTER COLUMN {$columna} TYPE NUMERIC(38,2)");
        }
    }
};
