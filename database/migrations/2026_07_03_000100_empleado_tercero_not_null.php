<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V100 · Fase 1.b (cierre) — `empleados.tercero_id` obligatorio.
 *
 * Antes de poner el NOT NULL, reconcilia a los empleados que quedaron sin
 * tercero tras V99 (documentos duplicados o sin tercero):
 *   1. Los enlaza a un tercero existente por (empresa, documento) — si hay
 *      varios (duplicados), toma el de menor id.
 *   2. A los que no tienen ninguno, les crea el tercero desde los datos
 *      denormalizados del propio empleado y los enlaza.
 * Recién ahí exige el NOT NULL + unicidad (empresa, tercero). Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Enlazar a un tercero existente por documento (el de menor id si
        //    hay duplicados: son la misma persona, se fusionan aparte).
        //    `IS NOT DISTINCT FROM` compara null-safe: empareja aunque empresa_id
        //    sea NULL (donde `=` daría siempre falso).
        DB::statement(<<<'SQL'
            UPDATE empleados e
               SET tercero_id = (
                   SELECT MIN(t.id) FROM tercero t
                    WHERE t.deleted_at IS NULL
                      AND t.empresa_id IS NOT DISTINCT FROM e.empresa_id
                      AND t.numero_documento = e.numero_documento
               )
             WHERE e.tercero_id IS NULL
               AND e.numero_documento IS NOT NULL
               AND EXISTS (
                   SELECT 1 FROM tercero t
                    WHERE t.deleted_at IS NULL
                      AND t.empresa_id IS NOT DISTINCT FROM e.empresa_id
                      AND t.numero_documento = e.numero_documento
               )
        SQL);

        // 2) Crear el tercero para los que no tienen ninguno (desde el empleado).
        //    Solo si el empleado tiene número de documento (obligatorio en tercero).
        DB::statement(<<<'SQL'
            INSERT INTO tercero
                (empresa_id, tipo_documento, numero_documento, nombres, apellidos,
                 es_empleado, es_cliente, activo, created_at, updated_at)
            SELECT e.empresa_id, COALESCE(e.tipo_documento, 'CC'), e.numero_documento,
                   e.nombres, e.apellidos, TRUE, FALSE, TRUE, NOW(), NOW()
              FROM empleados e
             WHERE e.tercero_id IS NULL
               AND e.numero_documento IS NOT NULL
            ON CONFLICT (empresa_id, numero_documento) DO NOTHING
        SQL);

        DB::statement(<<<'SQL'
            UPDATE empleados e
               SET tercero_id = t.id
              FROM tercero t
             WHERE e.tercero_id IS NULL
               AND e.numero_documento IS NOT NULL
               AND t.deleted_at IS NULL
               AND t.empresa_id IS NOT DISTINCT FROM e.empresa_id
               AND t.numero_documento = e.numero_documento
        SQL);

        // 3) Rol EMPLEADO + marca es_empleado en los terceros vinculados.
        DB::statement(<<<'SQL'
            INSERT INTO tercero_rol (tercero_id, rol)
            SELECT DISTINCT e.tercero_id, 'EMPLEADO'
              FROM empleados e
             WHERE e.tercero_id IS NOT NULL
            ON CONFLICT DO NOTHING
        SQL);

        DB::statement(<<<'SQL'
            UPDATE tercero t
               SET es_empleado = TRUE
             WHERE EXISTS (SELECT 1 FROM empleados e WHERE e.tercero_id = t.id)
        SQL);

        // 4) Guardarraíl: no debe quedar ningún huérfano.
        $huerfanos = DB::table('empleados')->whereNull('tercero_id')->count();
        if ($huerfanos > 0) {
            throw new \RuntimeException(
                "V100 abortada: {$huerfanos} empleados sin tercero_id — no tienen número de documento, " .
                "así que no se les puede crear un tercero. Complétales el documento o elimínalos si son basura. " .
                "Revisa: SELECT id, empresa_id, nombres, apellidos, numero_documento FROM empleados WHERE tercero_id IS NULL;"
            );
        }

        // 5) NOT NULL + unicidad persona-empresa (idempotente).
        DB::statement('ALTER TABLE empleados ALTER COLUMN tercero_id SET NOT NULL');
        DB::statement(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'uq_empleado_tercero'
                ) THEN
                    ALTER TABLE empleados
                        ADD CONSTRAINT uq_empleado_tercero UNIQUE (empresa_id, tercero_id);
                END IF;
            END $$
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE empleados DROP CONSTRAINT IF EXISTS uq_empleado_tercero');
        DB::statement('ALTER TABLE empleados ALTER COLUMN tercero_id DROP NOT NULL');
    }
};
