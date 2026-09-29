<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V118 — nomina contrato cierre.
 *
 * Traducción fiel de la migración Flyway V118__nomina_contrato_cierre.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V118: Fase 2 (cierre) — la nómina cuelga del contrato ───────────────────
--
-- Cierra lo que V103 dejó abierto: pone contrato_id NOT NULL y reemplaza
-- uq_nomina_empleado_periodo por uq_nomina_contrato_periodo.
--
-- ╔═══════════════════════════════════════════════════════════════════════════╗
-- ║ ⚠️ PRECONDICIONES                                                         ║
-- ╠═══════════════════════════════════════════════════════════════════════════╣
-- ║ 1. V102 y V103 aplicadas.                                                 ║
-- ║                                                                           ║
-- ║ 2. CÓDIGO DESPLEGADO (ya escrito):                                        ║
-- ║      · NominaEntity.contrato                                              ║
-- ║      · NominaService.liquidarContrato(periodoId, contratoId, empresaId)   ║
-- ║      · liquidarPeriodoCompleto() itera contratos vigentes                 ║
-- ║      · EmpleadoServiceImpl crea el contrato inicial                       ║
-- ║                                                                           ║
-- ║ 3. Sin nóminas huérfanas:                                                 ║
-- ║      SELECT COUNT(*) FROM nomina WHERE contrato_id IS NULL;   -- = 0      ║
-- ║                                                                           ║
-- ║ 4. Todo empleado activo con contrato:                                     ║
-- ║      SELECT COUNT(*) FROM empleados e                                     ║
-- ║       WHERE e.activo = TRUE                                               ║
-- ║         AND NOT EXISTS (SELECT 1 FROM contrato_laboral c                  ║
-- ║                          WHERE c.empleado_id = e.id                       ║
-- ║                            AND c.deleted_at IS NULL);        -- = 0      ║
-- ╚═══════════════════════════════════════════════════════════════════════════╝
--
-- ESTA ES LA MIGRACIÓN QUE HABILITA EL MULTI-VÍNCULO. Mientras
-- uq_nomina_empleado_periodo siga viva, un empleado con dos contratos NO puede
-- tener dos nóminas en el mismo período, aunque contrato_id exista.

-- ── Guardarraíles ───────────────────────────────────────────────────────────
DO $$
DECLARE
    huerfanas INT;
    sin_contrato INT;
BEGIN
    SELECT COUNT(*) INTO huerfanas FROM nomina WHERE contrato_id IS NULL;
    IF huerfanas > 0 THEN
        RAISE EXCEPTION
            'V118 abortada: % nóminas sin contrato_id. Correr el backfill de V103 y verificar. Consulta: SELECT id, empleado_id, periodo_id FROM nomina WHERE contrato_id IS NULL;',
            huerfanas;
    END IF;

    SELECT COUNT(*) INTO sin_contrato
      FROM empleados e
     WHERE e.activo = TRUE
       AND NOT EXISTS (SELECT 1 FROM contrato_laboral c
                        WHERE c.empleado_id = e.id AND c.deleted_at IS NULL);
    IF sin_contrato > 0 THEN
        RAISE EXCEPTION
            'V118 abortada: % empleados activos sin contrato. No podrán liquidarse. Consulta: SELECT id, nombres, apellidos FROM empleados e WHERE e.activo AND NOT EXISTS (SELECT 1 FROM contrato_laboral c WHERE c.empleado_id = e.id AND c.deleted_at IS NULL);',
            sin_contrato;
    END IF;
END $$
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── El cierre ───────────────────────────────────────────────────────────────
ALTER TABLE nomina
    ALTER COLUMN contrato_id SET NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE nomina
    DROP CONSTRAINT IF EXISTS uq_nomina_empleado_periodo
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Una nómina por CONTRATO por período. Un empleado con dos contratos activos
-- genera dos nóminas — que es exactamente el punto.
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'uq_nomina_contrato_periodo') THEN
        ALTER TABLE nomina
            ADD CONSTRAINT uq_nomina_contrato_periodo UNIQUE (contrato_id, periodo_id);
    END IF;
END $$
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN nomina.empleado_id IS
    'DEPRECADO como eje de liquidación — usar contrato_id. Se conserva para '
    'consultas y reportes por persona. Derivable: contrato.empleado_id.'
MIG_SQL);
    }

    public function down(): void
    {
        // Migración de datos/columnas (ver up). Reversa no automática.
    }
};
