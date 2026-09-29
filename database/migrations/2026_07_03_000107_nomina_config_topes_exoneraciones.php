<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V107 — nomina config topes exoneraciones.
 *
 * Traducción fiel de la migración Flyway V107__nomina_config_topes_exoneraciones.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V107: Fase 0 — topes legales y exoneraciones ────────────────────────────
--
-- BUG QUE ARREGLA (B3): faltan tres reglas de ley que hoy no existen:
--
--   1. TOPE DE IBC (25 SMMLV). Sin él, a un salario alto se le cotiza sobre
--      todo el devengado en vez de sobre el tope.
--
--   2. FONDO DE SOLIDARIDAD PENSIONAL. Aporte adicional escalonado sobre
--      4 SMMLV. Hoy no se descuenta → el empleado queda debiendo.
--
--   3. EXONERACIÓN LEY 1607 — EL MÁS CARO. Empresas con empleados que
--      devengan menos de 10 SMMLV NO pagan salud empleador, SENA ni ICBF.
--      Hoy se les cobran esos aportes. Se le está cobrando de más al cliente.
--
-- Parametrizado por empresa, no hardcodeado: la exoneración depende del tipo
-- de sociedad y los topes cambian por ley.
--
-- Seguro sin tocar código: agrega columnas con DEFAULT.

ALTER TABLE nomina_config
    -- Exoneración Ley 1607 (art. 114-1 ET). Aplica a sociedades declarantes
    -- de renta. NO aplica a personas naturales con menos de 2 empleados,
    -- ni a entidades sin ánimo de lucro.
    ADD COLUMN IF NOT EXISTS aplica_exoneracion_1607 BOOLEAN NOT NULL DEFAULT FALSE,

    -- Umbral de la exoneración, en SMMLV. Por debajo de esto, el empleador
    -- no paga salud/SENA/ICBF por ese empleado.
    ADD COLUMN IF NOT EXISTS umbral_exoneracion_smmlv NUMERIC(5,2) NOT NULL DEFAULT 10,

    -- Tope máximo del IBC, en SMMLV.
    ADD COLUMN IF NOT EXISTS tope_ibc_smmlv NUMERIC(5,2) NOT NULL DEFAULT 25,

    -- Fondo de solidaridad pensional: aplica sobre 4 SMMLV.
    ADD COLUMN IF NOT EXISTS aplica_fondo_solidaridad BOOLEAN NOT NULL DEFAULT TRUE,

    -- Salario integral: el IBC es el 70% (factor prestacional 30%).
    ADD COLUMN IF NOT EXISTS factor_salario_integral NUMERIC(5,2) NOT NULL DEFAULT 70
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN nomina_config.aplica_exoneracion_1607 IS
    'Ley 1607 art. 114-1: sociedades declarantes de renta no pagan salud empleador, '
    'SENA ni ICBF por empleados que devenguen menos de umbral_exoneracion_smmlv. '
    'DEFAULT FALSE por prudencia: activarlo es decisión del cliente con su contador.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN nomina_config.tope_ibc_smmlv IS
    'Tope del IBC. El aporte se calcula sobre MIN(baseIbc, tope * smmlv).'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Rangos del fondo de solidaridad pensional ───────────────────────────────
-- Escalonado. Cambia por ley → tabla, no constantes.
-- El aporte se divide en dos: solidaridad (1%) y subsistencia (0.2% a 1%).
CREATE TABLE IF NOT EXISTS fondo_solidaridad_rango (
    id                     BIGSERIAL     PRIMARY KEY,
    agno                   INT           NOT NULL,
    smmlv_desde            NUMERIC(6,2)  NOT NULL,
    smmlv_hasta            NUMERIC(6,2),            -- NULL = sin tope
    pct_solidaridad        NUMERIC(5,3)  NOT NULL DEFAULT 0,
    pct_subsistencia       NUMERIC(5,3)  NOT NULL DEFAULT 0,
    CONSTRAINT uq_fsp_rango UNIQUE (agno, smmlv_desde)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON TABLE fondo_solidaridad_rango IS
    'Rangos del fondo de solidaridad pensional por año. El total a descontar '
    'es (pct_solidaridad + pct_subsistencia) sobre el IBC de pensión.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ⚠️ REVISAR CON CONTADOR ANTES DE USAR.
-- Estos valores son la escala vigente al momento de escribir el plan.
-- Verificar contra la norma del año que corresponda antes de liquidar.
INSERT INTO fondo_solidaridad_rango (agno, smmlv_desde, smmlv_hasta, pct_solidaridad, pct_subsistencia)
VALUES
    (2026,  4.00, 16.00, 1.000, 0.000),
    (2026, 16.00, 17.00, 1.000, 0.200),
    (2026, 17.00, 18.00, 1.000, 0.400),
    (2026, 18.00, 19.00, 1.000, 0.600),
    (2026, 19.00, 20.00, 1.000, 0.800),
    (2026, 20.00, NULL,  1.000, 1.000)
ON CONFLICT (agno, smmlv_desde) DO NOTHING
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Conceptos que el motor necesita y V104 no semilló ───────────────────────
-- El fondo de solidaridad no puede ser un concepto porcentual normal: su tarifa
-- sale de un rango, no de un % fijo. Por eso base = MANUAL.
INSERT INTO concepto_nomina
    (empresa_id, codigo, nombre, clase, constituye_ibc, base, vigente_desde, orden, codigo_dian)
VALUES
    (NULL, 'DED_FONDO_SOLIDARIDAD', 'Fondo de solidaridad pensional', 'DEDUCCION', FALSE, 'MANUAL', '2026-01-01', 120, 'FondoSP'),
    (NULL, 'DED_OTROS',             'Otras deducciones',              'DEDUCCION', FALSE, 'MANUAL', '2026-01-01', 700, 'Libranza')
ON CONFLICT (empresa_id, codigo, vigente_desde) DO NOTHING
MIG_SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('fondo_solidaridad_rango');
    }
};
