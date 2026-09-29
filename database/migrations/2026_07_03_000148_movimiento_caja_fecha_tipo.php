<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V148 — corrige el tipo de movimiento_caja.fecha.
 *
 * Traducción de la migración Flyway V148__movimiento_caja_fecha_tipo.sql
 * (aura-back-old).
 *
 * La V145 daba por hecho que la columna `fecha` no existía y la agregaba con
 * ADD COLUMN IF NOT EXISTS. Pero la columna existe desde que se creó la tabla
 * (2024_01_02_000033_create_movimiento_caja_table), como TIMESTAMP con
 * DEFAULT now(): nunca estuvo mapeada en la entidad, así que en la práctica
 * guardaba lo mismo que `created_at`. El IF NOT EXISTS no hizo nada y la
 * columna se quedó en timestamp, mientras la entidad la declara LocalDate. Con
 * ddl-auto=validate la aplicación no arranca:
 *
 *   Schema-validation: wrong column type encountered in column [fecha] in table
 *   [movimiento_caja]; found [timestamp], but expecting [date]
 *
 * Esta migración hace la conversión que faltaba. La V145 ya quedó corregida
 * para instalaciones nuevas; esta existe para las bases donde la V145 vieja ya
 * se aplicó. En una base ya correcta no hace nada.
 *
 * No se pierde información: el instante exacto sigue en `created_at`, que es la
 * columna con la que el código ordena los movimientos del turno.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            DO $$
            BEGIN
                IF EXISTS (
                    SELECT 1 FROM information_schema.columns
                     WHERE table_name = 'movimiento_caja'
                       AND column_name = 'fecha'
                       AND data_type <> 'date'
                ) THEN
                    -- El DEFAULT se quita antes de cambiar el tipo: Postgres no
                    -- puede convertir un default de timestamp a una columna date.
                    ALTER TABLE movimiento_caja ALTER COLUMN fecha DROP DEFAULT;
                    ALTER TABLE movimiento_caja ALTER COLUMN fecha TYPE DATE USING fecha::date;
                    ALTER TABLE movimiento_caja ALTER COLUMN fecha SET DEFAULT CURRENT_DATE;
                END IF;
            END $$;
        SQL);

        DB::statement('UPDATE movimiento_caja SET fecha = created_at::date WHERE fecha IS NULL');
        DB::statement('ALTER TABLE movimiento_caja ALTER COLUMN fecha SET NOT NULL');
    }

    public function down(): void
    {
        // Volver a timestamp no restauraría la hora original — se perdió al
        // castear a date, y de todos modos vive en `created_at`. Revertir solo
        // dejaría la columna con horas en medianoche fingiendo un dato que no
        // existe, así que este cambio no se deshace.
    }
};
