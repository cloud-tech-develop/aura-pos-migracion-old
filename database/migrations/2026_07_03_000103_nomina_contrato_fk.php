<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V103 — nomina contrato fk.
 *
 * Traducción fiel de la migración Flyway V103__nomina_contrato_fk.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V103: Fase 2 (cierre) — la nómina se liquida contra un CONTRATO ─────────
--
-- Hoy `uq_nomina_empleado_periodo UNIQUE (empleado_id, periodo_id)` impone una
-- nómina por empleado por período. Eso es justo lo que impide el multi-vínculo:
-- si alguien tiene dos contratos, cada uno debe liquidarse por separado.
--
-- ╔═══════════════════════════════════════════════════════════════════════════╗
-- ║ ⛔ REQUIERE CAMBIO DE CÓDIGO ANTES (parcialmente)                         ║
-- ╠═══════════════════════════════════════════════════════════════════════════╣
-- ║ El ALTER que pone contrato_id NOT NULL y cambia la unicidad exige que     ║
-- ║ NominaEntity tenga `contrato` y que NominaServiceImpl.liquidar() lo setee.║
-- ║                                                                           ║
-- ║ Esta migración deja contrato_id NULLABLE a propósito: es segura de correr ║
-- ║ ya. El cierre (NOT NULL + cambio de unicidad) está al final, comentado.   ║
-- ╚═══════════════════════════════════════════════════════════════════════════╝

ALTER TABLE nomina
    ADD COLUMN IF NOT EXISTS contrato_id BIGINT REFERENCES contrato_laboral(id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_nomina_contrato ON nomina(contrato_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN nomina.contrato_id IS
    'Contrato liquidado. Reemplaza a empleado_id como eje: una persona con dos '
    'contratos activos genera dos nóminas por período.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Backfill: nómina está vacía, pero por si hay datos de prueba.
-- Toma el contrato principal activo del empleado.
UPDATE nomina n
   SET contrato_id = c.id
  FROM contrato_laboral c
 WHERE n.contrato_id IS NULL
   AND c.empleado_id = n.empleado_id
   AND c.deleted_at IS NULL
   AND c.es_principal = TRUE
   AND (SELECT COUNT(*) FROM contrato_laboral c2
         WHERE c2.empleado_id = n.empleado_id
           AND c2.deleted_at IS NULL
           AND c2.es_principal = TRUE) = 1
MIG_SQL);
    }

    public function down(): void
    {
        // Migración de datos/columnas (ver up). Reversa no automática.
    }
};
