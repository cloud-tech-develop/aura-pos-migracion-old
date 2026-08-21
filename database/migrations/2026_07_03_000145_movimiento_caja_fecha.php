<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V145 — el movimiento de caja tiene fecha propia.
 *
 * Traducción de la migración Flyway V145__movimiento_caja_fecha.sql
 * (aura-back-old).
 *
 * Hasta ahora el movimiento de caja solo tenía `created_at`, o sea la fecha en
 * que alguien lo digitó. Eso confundía dos cosas distintas:
 *
 *   fecha           → cuándo salió (o entró) la plata físicamente. Manda en el
 *                     arqueo: es la que dice a qué turno pertenece el dinero.
 *   fecha_documento → la de la factura o el gasto que lo originó. Manda en la
 *                     contabilidad, el IVA y la exógena.
 *
 * Al no distinguirlas, una factura de compra con fecha de hace tres días caía en
 * el arqueo de HOY y el cajero de hoy terminaba respondiendo por plata que él no
 * gastó. Con las dos fechas separadas el movimiento puede decir "la plata salió
 * hoy, pero el documento es del 18" y el cierre lo muestra aparte.
 *
 * `origen_tipo` / `origen_id` cierran el círculo: desde el arqueo se puede abrir
 * el documento que produjo el movimiento, que antes había que adivinar leyendo
 * el texto del concepto.
 *
 * IMPORTANTE: el backend corre con ddl-auto=validate, así que no arranca hasta
 * que esta migración se aplique. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        // OJO: la columna `fecha` YA EXISTE desde que se creo la tabla, como
        // TIMESTAMP con DEFAULT now(). Nunca estuvo mapeada en la entidad, asi
        // que en la practica guardaba lo mismo que `created_at`: el instante de
        // digitacion. Aqui se la reutiliza con su significado nuevo -- el DIA en
        // que se movio la plata -- y por eso hay que convertir el tipo, no
        // agregar la columna.
        //
        // No se pierde informacion: el instante exacto sigue en `created_at`,
        // que es la columna con la que el codigo ordena los movimientos.
        DB::statement('ALTER TABLE movimiento_caja ADD COLUMN IF NOT EXISTS fecha DATE');

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
                END IF;
            END $$;
        SQL);

        // Lo unico que se sabe del historico es cuando se digito. Es el mejor
        // dato disponible y coincide con el comportamiento que el sistema tuvo
        // hasta hoy.
        DB::statement('UPDATE movimiento_caja SET fecha = created_at::date WHERE fecha IS NULL');

        DB::statement('ALTER TABLE movimiento_caja ALTER COLUMN fecha SET DEFAULT CURRENT_DATE');
        DB::statement('ALTER TABLE movimiento_caja ALTER COLUMN fecha SET NOT NULL');

        // NULL significa "el documento es del mismo día del movimiento". Solo se
        // informa cuando difiere, para que el cierre pueda filtrar por "distinto
        // de NULL" sin comparar contra la fecha del turno en cada fila.
        DB::statement('ALTER TABLE movimiento_caja ADD COLUMN IF NOT EXISTS fecha_documento DATE');

        // COMPRA | GASTO | DEVOLUCION | OBLIGACION | TRASLADO_FONDOS | MANUAL
        DB::statement('ALTER TABLE movimiento_caja ADD COLUMN IF NOT EXISTS origen_tipo VARCHAR(30)');
        DB::statement('ALTER TABLE movimiento_caja ADD COLUMN IF NOT EXISTS origen_id   BIGINT');

        // Marca que el turno no lo eligió el usuario sino que lo dedujo el
        // sistema (único turno abierto de la sucursal). Sirve para auditar los
        // movimientos que cayeron en una caja por inferencia y no por decisión
        // de alguien.
        DB::statement('ALTER TABLE movimiento_caja ADD COLUMN IF NOT EXISTS origen_inferido BOOLEAN NOT NULL DEFAULT FALSE');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_mov_caja_fecha  ON movimiento_caja (fecha)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mov_caja_origen ON movimiento_caja (origen_tipo, origen_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_mov_caja_origen');
        DB::statement('DROP INDEX IF EXISTS idx_mov_caja_fecha');

        DB::statement('ALTER TABLE movimiento_caja DROP COLUMN IF EXISTS origen_inferido');
        DB::statement('ALTER TABLE movimiento_caja DROP COLUMN IF EXISTS origen_id');
        DB::statement('ALTER TABLE movimiento_caja DROP COLUMN IF EXISTS origen_tipo');
        DB::statement('ALTER TABLE movimiento_caja DROP COLUMN IF EXISTS fecha_documento');
        DB::statement('ALTER TABLE movimiento_caja DROP COLUMN IF EXISTS fecha');
    }
};
