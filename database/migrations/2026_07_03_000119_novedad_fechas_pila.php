<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V119 — novedad fechas pila.
 *
 * Traducción fiel de la migración Flyway V119__novedad_fechas_pila.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V119: Fase 6 — fechas y subtipo en las novedades ────────────────────────
--
-- HALLAZGO: `nomina_novedad` no tiene fechas. Solo tipo, cantidad y valor.
--
-- Eso alcanza para liquidar (un valor es un valor), pero NO para PILA: la UGPP
-- exige el PAR DE FECHAS de cada ausentismo:
--
--   ige    → fecha_inicio_ige, fecha_fin_ige
--   lma    → fecha_inicio_lma, fecha_fin_lma
--   sln    → fecha_inicio_sln, fecha_fin_sln
--   vac_lr → fecha_inicio_vac_lr, fecha_fin_vac_lr
--   irl    → fecha_inicio_irl, fecha_fin_irl
--
-- Sin fechas, las banderas no se pueden reportar y el operador rechaza el
-- archivo. Tampoco se pueden repartir los días entre meses: una incapacidad
-- del 28 de marzo al 5 de abril aporta 4 días a marzo y 5 a abril.
--
-- Seguro sin tocar código: agrega columnas nullable.

ALTER TABLE nomina_novedad
    ADD COLUMN IF NOT EXISTS fecha_inicio DATE,
    ADD COLUMN IF NOT EXISTS fecha_fin    DATE,

    -- ── Subtipo: distingue casos que PILA reporta DISTINTO ──────────────────
    -- El CHECK de `tipo` tiene un solo 'INCAPACIDAD', pero:
    --   · incapacidad GENERAL (ige)      → la paga la EPS
    --   · incapacidad RIESGO LABORAL (irl) → la paga la ARL, y su IBC difiere
    -- Son banderas distintas en el registro tipo 02. Sin discriminarlas, se
    -- reporta mal el 100% de las incapacidades laborales.
    ADD COLUMN IF NOT EXISTS subtipo VARCHAR(30),

    -- Número de autorización de la incapacidad/licencia. La UGPP lo exige.
    ADD COLUMN IF NOT EXISTS numero_autorizacion VARCHAR(30)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN nomina_novedad.fecha_inicio IS
    'Inicio del hecho (no de la nómina donde se liquida). Requerido por PILA.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN nomina_novedad.fecha_fin IS
    'Fin del hecho. Puede exceder el período: los días se recortan al liquidar.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN nomina_novedad.subtipo IS
    'Discrimina dentro de un tipo. Para INCAPACIDAD: GENERAL | RIESGO_LABORAL. '
    'PILA las reporta como banderas distintas (ige vs irl) y con IBC distinto.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_novedad_fechas') THEN
        ALTER TABLE nomina_novedad
            ADD CONSTRAINT chk_novedad_fechas
                CHECK (fecha_fin IS NULL OR fecha_inicio IS NULL OR fecha_fin >= fecha_inicio);
    END IF;
END $$
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Las de asistencia (origen ASISTENCIA) podrían tener fecha derivable del
-- marcaje; las manuales no. No se hace backfill: no hay novedades cargadas.

-- ── Tipos que PILA necesita y el CHECK actual no tiene ──────────────────────
-- 'LICENCIA_NO_REMUNERADA' es la bandera `sln`: cotiza salud pero NO pensión.
-- Hoy no existe como tipo, así que ese caso no se puede ni registrar.
ALTER TABLE nomina_novedad DROP CONSTRAINT IF EXISTS chk_novedad_tipo
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE nomina_novedad
    ADD CONSTRAINT chk_novedad_tipo CHECK (tipo IN (
        'HORA_EXTRA_DIURNA', 'HORA_EXTRA_NOCTURNA', 'HORA_EXTRA_DOMINICAL',
        'HORA_EXTRA_FESTIVO', 'RECARGO_NOCTURNO',
        'INCAPACIDAD',                 -- usar `subtipo` para GENERAL | RIESGO_LABORAL
        'LICENCIA_REMUNERADA',
        'LICENCIA_NO_REMUNERADA',      -- nuevo: bandera sln
        'LICENCIA_MATERNIDAD',         -- nuevo: bandera lma
        'VACACIONES',                  -- nuevo: bandera vac_lr
        'SUSPENSION',                  -- nuevo: bandera sln
        'BONO', 'COMISION', 'PRESTAMO', 'EMBARGO',
        'OTRO_DEVENGO', 'OTRO_DESCUENTO'
    ))
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ⚠️ Los tipos nuevos deben marcarse en constituye_ibc según corresponda.
-- LICENCIA_NO_REMUNERADA y SUSPENSION no generan devengado: no constituyen IBC.
UPDATE nomina_novedad
   SET constituye_ibc = FALSE
 WHERE tipo IN ('LICENCIA_NO_REMUNERADA', 'SUSPENSION')
MIG_SQL);
    }

    public function down(): void
    {
        // Migración de datos/columnas (ver up). Reversa no automática.
    }
};
