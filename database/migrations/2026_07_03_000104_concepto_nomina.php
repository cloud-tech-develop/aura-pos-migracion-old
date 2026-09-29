<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V104 — concepto nomina.
 *
 * Traducción fiel de la migración Flyway V104__concepto_nomina.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V104: Fase 3.a — catálogo de conceptos de nómina ────────────────────────
--
-- Hoy los porcentajes son constantes compiladas en NominaServiceImpl:
--     private static final BigDecimal PCT_PRIMA = new BigDecimal("8.33");
-- Un cambio de ley, un devengado propio de un cliente o una bonificación
-- distinta por empresa = código nuevo + release + despliegue.
--
-- ⚠️ LO QUE NO SE COPIA DEL ERP DE REFERENCIA:
-- Ese sistema guarda PHP en base64 en una columna `formula` y lo ejecuta con
-- eval(). Es ejecución de código arbitrario para quien pueda editar un concepto,
-- no se puede testear, y hace imposible entender un cálculo leyendo el repo.
--
-- Aquí `base` es un ENUM ACOTADO, no una expresión. Cubre los casos reales sin
-- abrir esa puerta. Si algún día hace falta más expresividad: SpEL restringido
-- o un DSL propio. NUNCA eval() de código almacenado.
--
-- Seguro sin tocar código: solo crea tablas nuevas.

CREATE TABLE IF NOT EXISTS concepto_nomina (
    id             BIGSERIAL    PRIMARY KEY,

    -- NULL = concepto global del sistema (los de ley).
    -- Con empresa_id = personalización de un cliente.
    empresa_id     INT          REFERENCES empresa(id),

    codigo         VARCHAR(30)  NOT NULL,
    nombre         VARCHAR(150) NOT NULL,

    clase          VARCHAR(20)  NOT NULL,
        -- DEVENGADO | DEDUCCION | APORTE_EMPLEADOR | PROVISION

    -- ¿Entra en la base de seguridad social?
    -- El auxilio de transporte NO. Un bono no salarial NO. Horas extra SÍ.
    constituye_ibc BOOLEAN      NOT NULL DEFAULT TRUE,

    -- Sobre qué se calcula. ENUM acotado — no es una expresión.
    base           VARCHAR(30)  NOT NULL,
        -- SALARIO              → salario proporcional a días trabajados
        -- SALARIO_MAS_AUXILIO  → base prestacional (prima, cesantías)
        -- IBC                  → base de seguridad social (sin auxilio)
        -- DEVENGADO_TOTAL      → todo lo devengado
        -- FIJO                 → valor_fijo, sin cálculo
        -- MANUAL               → lo captura el usuario (novedades)

    porcentaje     NUMERIC(7,4),
    valor_fijo     NUMERIC(15,2),

    -- Vigencia: lo que permite cambiar tarifas por ley SIN perder la capacidad
    -- de reliquidar períodos anteriores con las tarifas de su momento.
    vigente_desde  DATE         NOT NULL,
    vigente_hasta  DATE,

    -- Para la nómina electrónica: mapea a la etiqueta que exige la DIAN.
    codigo_dian    VARCHAR(30),

    orden          INT          NOT NULL DEFAULT 100,  -- orden de cálculo
    activo         BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at     TIMESTAMP    NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_cn_clase CHECK (clase IN
        ('DEVENGADO', 'DEDUCCION', 'APORTE_EMPLEADOR', 'PROVISION')),
    CONSTRAINT chk_cn_base CHECK (base IN
        ('SALARIO', 'SALARIO_MAS_AUXILIO', 'IBC', 'DEVENGADO_TOTAL', 'FIJO', 'MANUAL')),
    CONSTRAINT chk_cn_vigencia CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde),
    CONSTRAINT uq_concepto UNIQUE (empresa_id, codigo, vigente_desde)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_cn_empresa ON concepto_nomina(empresa_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_cn_vigente ON concepto_nomina(codigo, vigente_desde, vigente_hasta)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Dependencias entre conceptos ────────────────────────────────────────────
-- Qué conceptos entran en la base de cálculo de otro.
-- (En el ERP de referencia esto es `nom_conceptos_bases`.)
CREATE TABLE IF NOT EXISTS concepto_nomina_base (
    concepto_id      BIGINT NOT NULL REFERENCES concepto_nomina(id) ON DELETE CASCADE,
    concepto_base_id BIGINT NOT NULL REFERENCES concepto_nomina(id) ON DELETE CASCADE,
    PRIMARY KEY (concepto_id, concepto_base_id),
    CONSTRAINT chk_cnb_no_self CHECK (concepto_id <> concepto_base_id)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Ciclos (A depende de B, B depende de A) se validan en aplicación:
-- un CHECK no puede recorrer un grafo.

-- ── Semilla: conceptos de ley vigentes ──────────────────────────────────────
-- empresa_id NULL = global. Las tarifas salen de las constantes que hoy están
-- compiladas en NominaServiceImpl y de nomina_config.
--
-- ⚠️ REVISAR vigente_desde: se usa 2026-01-01. Ajustar al año real de entrada.
-- ⚠️ Los valores de SMMLV y auxilio de transporte siguen en nomina_config
--    porque son parámetros, no conceptos. No duplicarlos aquí.

INSERT INTO concepto_nomina
    (empresa_id, codigo, nombre, clase, constituye_ibc, base, porcentaje, vigente_desde, orden, codigo_dian)
VALUES
    -- ── Devengados ──────────────────────────────────────────────────────────
    (NULL, 'SALARIO',      'Salario básico',        'DEVENGADO', TRUE,  'SALARIO',        NULL, '2026-01-01', 10, 'Basico'),
    (NULL, 'AUX_TRANSP',   'Auxilio de transporte', 'DEVENGADO', FALSE, 'FIJO',           NULL, '2026-01-01', 20, 'Transporte'),
    --                                                            ^^^^^ NO es base de seguridad social

    -- ── Deducciones empleado ────────────────────────────────────────────────
    (NULL, 'DED_SALUD',    'Salud empleado',        'DEDUCCION', FALSE, 'IBC',          4.0000, '2026-01-01', 100, 'Salud'),
    (NULL, 'DED_PENSION',  'Pensión empleado',      'DEDUCCION', FALSE, 'IBC',          4.0000, '2026-01-01', 110, 'FondoPension'),

    -- ── Aportes empleador ───────────────────────────────────────────────────
    (NULL, 'APO_SALUD',    'Salud empleador',       'APORTE_EMPLEADOR', FALSE, 'IBC',   8.5000, '2026-01-01', 200, NULL),
    (NULL, 'APO_PENSION',  'Pensión empleador',     'APORTE_EMPLEADOR', FALSE, 'IBC',  12.0000, '2026-01-01', 210, NULL),
    (NULL, 'APO_CCF',      'Caja de compensación',  'APORTE_EMPLEADOR', FALSE, 'IBC',   4.0000, '2026-01-01', 220, NULL),
    (NULL, 'APO_ICBF',     'ICBF',                  'APORTE_EMPLEADOR', FALSE, 'IBC',   3.0000, '2026-01-01', 230, NULL),
    (NULL, 'APO_SENA',     'SENA',                  'APORTE_EMPLEADOR', FALSE, 'IBC',   2.0000, '2026-01-01', 240, NULL),
    -- ARL: la tarifa depende del nivel de riesgo del empleado → base MANUAL,
    -- el motor la resuelve desde contrato/centro de trabajo (Fase 5.5).
    (NULL, 'APO_ARL',      'ARL',                   'APORTE_EMPLEADOR', FALSE, 'MANUAL', NULL, '2026-01-01', 250, NULL),

    -- ── Provisiones ─────────────────────────────────────────────────────────
    -- Prima y cesantías: base = salario + auxilio.  Vacaciones: solo salario.
    (NULL, 'PRO_PRIMA',     'Provisión prima',              'PROVISION', FALSE, 'SALARIO_MAS_AUXILIO',  8.3300, '2026-01-01', 300, NULL),
    (NULL, 'PRO_CESANTIAS', 'Provisión cesantías',          'PROVISION', FALSE, 'SALARIO_MAS_AUXILIO',  8.3300, '2026-01-01', 310, NULL),
    (NULL, 'PRO_INT_CES',   'Provisión intereses cesantías','PROVISION', FALSE, 'MANUAL',              12.0000, '2026-01-01', 320, NULL),
    (NULL, 'PRO_VACAC',     'Provisión vacaciones',         'PROVISION', FALSE, 'SALARIO',              4.1700, '2026-01-01', 330, NULL)
ON CONFLICT (empresa_id, codigo, vigente_desde) DO NOTHING
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Intereses de cesantías: se calculan SOBRE la provisión de cesantías.
-- Por eso base = MANUAL y la dependencia se declara aquí.
INSERT INTO concepto_nomina_base (concepto_id, concepto_base_id)
SELECT c.id, b.id
  FROM concepto_nomina c, concepto_nomina b
 WHERE c.codigo = 'PRO_INT_CES' AND c.empresa_id IS NULL
   AND b.codigo = 'PRO_CESANTIAS' AND b.empresa_id IS NULL
ON CONFLICT DO NOTHING
MIG_SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('concepto_nomina_base');
        Schema::dropIfExists('concepto_nomina');
    }
};
