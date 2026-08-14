<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V99 — empleado tercero fk.
 *
 * Traducción fiel de la migración Flyway V99__empleado_tercero_fk.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V99: Fase 1.b — enlazar `empleados` a `tercero` ─────────────────────────
--
-- Hoy `empleados` duplica nombres, apellidos, tipo_documento y numero_documento
-- respecto a `tercero`. El día que un empleado sea también proveedor, es la
-- misma persona dos veces sin forma de saberlo.
--
-- La nómina electrónica necesita datos fiscales y de identificación del
-- trabajador que viven (o deben vivir) en `tercero`. Sin esto habría que
-- duplicarlos otra vez.
--
-- Punto a favor: `tercero.es_empleado` YA existe — esta fase estaba anticipada.
--
-- ⚠️ Esta migración solo agrega la columna. El emparejamiento va en el script
--    de reconciliación (ver comentario al final). NO automatizar los ambiguos.

ALTER TABLE empleados
    ADD COLUMN IF NOT EXISTS tercero_id BIGINT REFERENCES tercero(id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_empleados_tercero ON empleados(tercero_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN empleados.tercero_id IS
    'Identidad de la persona. `empleados` pasa a ser el vínculo persona-empresa; '
    'los datos de identificación se leen de `tercero`.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Emparejamiento automático: solo los casos SIN ambigüedad ────────────────
-- Empareja por (empresa_id, tipo_documento, numero_documento) cuando hay
-- exactamente UN tercero candidato. Los demás quedan NULL para revisión.
UPDATE empleados e
   SET tercero_id = t.id
  FROM tercero t
 WHERE e.tercero_id IS NULL
   AND t.deleted_at IS NULL
   AND t.empresa_id      = e.empresa_id
   AND t.tipo_documento   = e.tipo_documento
   AND t.numero_documento = e.numero_documento
   AND (
        SELECT COUNT(*) FROM tercero t2
         WHERE t2.deleted_at IS NULL
           AND t2.empresa_id      = e.empresa_id
           AND t2.tipo_documento   = e.tipo_documento
           AND t2.numero_documento = e.numero_documento
       ) = 1
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Marcar el rol EMPLEADO en los terceros emparejados
INSERT INTO tercero_rol (tercero_id, rol)
SELECT DISTINCT e.tercero_id, 'EMPLEADO'
  FROM empleados e
 WHERE e.tercero_id IS NOT NULL
ON CONFLICT DO NOTHING
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE tercero t
   SET es_empleado = TRUE
 WHERE EXISTS (SELECT 1 FROM empleados e WHERE e.tercero_id = t.id)
MIG_SQL);
    }

    public function down(): void
    {
        // Migración de datos/columnas (ver up). Reversa no automática.
    }
};
