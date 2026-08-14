<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V106 — novedad constituye ibc.
 *
 * Traducción fiel de la migración Flyway V106__novedad_constituye_ibc.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V106: Fase 0 — marcar qué novedades constituyen IBC ─────────────────────
--
-- BUG QUE ARREGLA (B2): hoy `calcular()` suma indiscriminadamente todas las
-- novedades no-deducción en `novedadesDevengadas`, y esa suma entra a la base
-- de seguridad social. Pero:
--   · HORA_EXTRA_*, COMISION  → SÍ son base de seguridad social
--   · BONO no salarial        → NO lo es
--   · INCAPACIDAD, LICENCIA   → tienen tratamiento propio
--
-- Sin esta marca, el IBC sale inflado o deflactado según el tipo de novedad.
--
-- Contexto: nómina no la usa nadie todavía → no hay novedades cargadas, así
-- que el backfill es sobre tabla vacía. Se define bien desde cero.
--
-- Seguro sin tocar código: agrega columna con DEFAULT.

ALTER TABLE nomina_novedad
    ADD COLUMN IF NOT EXISTS constituye_ibc BOOLEAN NOT NULL DEFAULT TRUE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN nomina_novedad.constituye_ibc IS
    'Si el valor entra en la base de seguridad social. Horas extra y comisiones SÍ; '
    'bonos no salariales NO. Ver Fase 0 del PLAN_MIGRACION_NOMINA.md.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Backfill por tipo ───────────────────────────────────────────────────────
-- ⚠️ DECISIÓN DE NEGOCIO: `BONO` se marca como NO salarial.
--    Es lo habitual (bono no constitutivo de salario pactado como tal), pero
--    si algún cliente paga bonos salariales, hay que discriminarlos.
--    Hoy no hay datos → la decisión aplica solo hacia adelante.
UPDATE nomina_novedad
   SET constituye_ibc = FALSE
 WHERE tipo IN (
        'BONO',                 -- no salarial (ver advertencia arriba)
        'INCAPACIDAD',          -- la paga EPS/ARL, no es salario
        'LICENCIA_REMUNERADA',
        'PRESTAMO',             -- deducción
        'EMBARGO',              -- deducción
        'OTRO_DESCUENTO'        -- deducción
       )
MIG_SQL);
    }

    public function down(): void
    {
        // Migración de datos/columnas (ver up). Reversa no automática.
    }
};
