<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V112 — prestaciones completas.
 *
 * Traducción fiel de la migración Flyway V112__prestaciones_completas.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V112: Fase 8 — prestaciones sociales y liquidación definitiva ───────────
--
-- Hoy `liquidacion_prestacion` (V76) declara en su CHECK los tipos PRIMA,
-- VACACIONES, CESANTIAS, INTERESES_CESANTIAS, LIQUIDACION_DEFINITIVA e
-- INDEMNIZACION, pero en la práctica solo se usan PRIMA y VACACIONES.
-- Y `nomina` solo PROVISIONA cesantías: nunca las liquida ni las consigna.
--
-- Seguro sin tocar código: agrega columnas nullable y crea tablas.

ALTER TABLE liquidacion_prestacion
    -- La prestación se liquida contra un CONTRATO, no contra un empleado:
    -- si hay dos vínculos, cada uno tiene su liquidación.
    ADD COLUMN IF NOT EXISTS contrato_id        BIGINT REFERENCES contrato_laboral(id),

    -- Fondo al que se consignan las cesantías. Tercero con rol AFP.
    ADD COLUMN IF NOT EXISTS fondo_cesantias_id BIGINT REFERENCES tercero(id),

    -- ⚠️ BASE PRESTACIONAL: NO es el salario básico.
    -- Incluye auxilio de transporte, horas extra, comisiones y todo lo
    -- salarial del período de referencia.
    -- ES DISTINTA de la base de seguridad social (Fase 0) y de la base de
    -- retefuente (Fase 4.5). SON TRES BASES DISTINTAS. No confundirlas.
    ADD COLUMN IF NOT EXISTS base_prestacional  NUMERIC(15,2) NOT NULL DEFAULT 0,

    ADD COLUMN IF NOT EXISTS dias_liquidados    INT           NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS causa_retiro       VARCHAR(40),
    ADD COLUMN IF NOT EXISTS nomina_id          BIGINT REFERENCES nomina(id),
    ADD COLUMN IF NOT EXISTS traza              JSONB
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_prestacion_contrato ON liquidacion_prestacion(contrato_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN liquidacion_prestacion.base_prestacional IS
    'Salario + auxilio de transporte + promedio de lo salarial variable del '
    'período de referencia. DISTINTA del IBC y de la base de retefuente.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN liquidacion_prestacion.causa_retiro IS
    'Determina si hay indemnización y cómo se calcula. '
    'JUSTA_CAUSA | SIN_JUSTA_CAUSA | RENUNCIA | MUTUO_ACUERDO | '
    'VENCIMIENTO_TERMINO | OBRA_TERMINADA | MUERTE'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Detalle por concepto de la liquidación ──────────────────────────────────
CREATE TABLE IF NOT EXISTS liquidacion_prestacion_detalle (
    id             BIGSERIAL     PRIMARY KEY,
    liquidacion_id BIGINT        NOT NULL REFERENCES liquidacion_prestacion(id) ON DELETE CASCADE,
    concepto_id    BIGINT        NOT NULL REFERENCES concepto_nomina(id),
    base           NUMERIC(15,2) NOT NULL DEFAULT 0,
    dias           INT           NOT NULL DEFAULT 0,
    valor          NUMERIC(15,2) NOT NULL DEFAULT 0,
    traza          JSONB,
    created_at     TIMESTAMP     NOT NULL DEFAULT NOW()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_lpd_liquidacion ON liquidacion_prestacion_detalle(liquidacion_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Conceptos de prestaciones y liquidación definitiva ──────────────────────
INSERT INTO concepto_nomina
    (empresa_id, codigo, nombre, clase, constituye_ibc, base, vigente_desde, orden, codigo_dian)
VALUES
    (NULL, 'PRIMA',          'Prima de servicios',            'DEVENGADO', FALSE, 'SALARIO_MAS_AUXILIO', '2026-01-01', 500, 'Primas'),
    (NULL, 'CESANTIAS',      'Cesantías',                     'DEVENGADO', FALSE, 'SALARIO_MAS_AUXILIO', '2026-01-01', 510, 'Cesantias'),
    (NULL, 'INT_CESANTIAS',  'Intereses sobre cesantías',     'DEVENGADO', FALSE, 'MANUAL',              '2026-01-01', 520, 'Cesantias'),
    (NULL, 'VACACIONES',     'Vacaciones',                    'DEVENGADO', TRUE,  'SALARIO',             '2026-01-01', 530, 'Vacaciones'),
    (NULL, 'INDEMNIZACION',  'Indemnización por despido',     'DEVENGADO', FALSE, 'MANUAL',              '2026-01-01', 540, 'Indemnizacion'),
    (NULL, 'BONIF_RETIRO',   'Bonificación por retiro',       'DEVENGADO', FALSE, 'MANUAL',              '2026-01-01', 550, 'Bonificaciones')
ON CONFLICT (empresa_id, codigo, vigente_desde) DO NOTHING
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Intereses de cesantías = 12% anual sobre las cesantías del período.
INSERT INTO concepto_nomina_base (concepto_id, concepto_base_id)
SELECT c.id, b.id
  FROM concepto_nomina c, concepto_nomina b
 WHERE c.codigo = 'INT_CESANTIAS' AND c.empresa_id IS NULL
   AND b.codigo = 'CESANTIAS'     AND b.empresa_id IS NULL
ON CONFLICT DO NOTHING
MIG_SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidacion_prestacion_detalle');
    }
};
