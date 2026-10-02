<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V184 — el costo del producto y el del kardex guardan 6 decimales.
 *
 * Traducción de la migración Flyway V184__costo_promedio_seis_decimales.sql
 * (aura-back-old).
 *
 * producto.costo pasa a ser el costo promedio ponderado: cada compra mezcla lo
 * que había con lo que entra. Con 2 decimales cada mezcla redondea y el error
 * se acumula hasta que el inventario valorizado deja de cuadrar con la 1435.
 * movimiento_inventario.costo_historico sube igual para que el kardex guarde el
 * mismo costo unitario con que se valorizó la salida.
 *
 * Ampliar la escala no pierde datos. Idempotente: solo altera las columnas que
 * aún tienen menos de 6 decimales.
 */
return new class extends Migration
{
    private const COLUMNAS = [
        ['producto', 'costo'],
        ['movimiento_inventario', 'costo_historico'],
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
        // Volver a 2 decimales redondea los costos promedio guardados: solo en
        // una base de prueba.
        foreach (self::COLUMNAS as [$tabla, $columna]) {
            DB::statement("ALTER TABLE {$tabla} ALTER COLUMN {$columna} TYPE NUMERIC(38,2)");
        }
    }
};
