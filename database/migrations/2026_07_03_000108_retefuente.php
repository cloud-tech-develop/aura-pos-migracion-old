<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V108 — retefuente.
 *
 * Traducción fiel de la migración Flyway V108__retefuente.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V108: Fase 4.5 — retención en la fuente sobre salarios ──────────────────
--
-- Obligatoria por ley. VA ANTES DE LA FASE 5: el XML de nómina electrónica
-- lleva la retención como deducción. Enviar sin ella = documentos incompletos.
--
-- Hoy no existe nada de esto en el sistema.
--
-- Seguro sin tocar código: crea tablas y agrega columnas con DEFAULT.

-- ── Valor del UVT por año. Lo fija la DIAN. ─────────────────────────────────
CREATE TABLE IF NOT EXISTS uvt_valor (
    agno  INT           PRIMARY KEY,
    valor NUMERIC(15,2) NOT NULL
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ⚠️ VERIFICAR CON LA RESOLUCIÓN DIAN DEL AÑO. Valor de referencia.
INSERT INTO uvt_valor (agno, valor) VALUES (2026, 49799.00)
ON CONFLICT (agno) DO NOTHING
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Tabla de retención (art. 383 ET) ────────────────────────────────────────
-- Cambia por ley → tabla, no constantes en el código.
CREATE TABLE IF NOT EXISTS retefuente_rango (
    id         BIGSERIAL     PRIMARY KEY,
    agno       INT           NOT NULL,
    uvt_desde  NUMERIC(12,2) NOT NULL,
    uvt_hasta  NUMERIC(12,2),            -- NULL = último rango, sin tope
    tarifa     NUMERIC(5,2)  NOT NULL,   -- %
    uvt_resta  NUMERIC(12,2) NOT NULL DEFAULT 0,  -- (base - uvt_resta) * tarifa
    uvt_suma   NUMERIC(12,2) NOT NULL DEFAULT 0,  -- + uvt_suma
    CONSTRAINT uq_rf_rango UNIQUE (agno, uvt_desde)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON TABLE retefuente_rango IS
    'Tabla del art. 383 ET. Fórmula: retencion_uvt = (base_uvt - uvt_resta) * tarifa/100 + uvt_suma'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ⚠️ VERIFICAR CON CONTADOR ANTES DE LIQUIDAR. Escala de referencia.
INSERT INTO retefuente_rango (agno, uvt_desde, uvt_hasta, tarifa, uvt_resta, uvt_suma)
VALUES
    (2026,    0.00,   95.00,  0.00,    0.00,    0.00),
    (2026,   95.00,  150.00, 19.00,   95.00,    0.00),
    (2026,  150.00,  360.00, 28.00,  150.00,   10.00),
    (2026,  360.00,  640.00, 33.00,  360.00,   69.00),
    (2026,  640.00,  945.00, 35.00,  640.00,  162.00),
    (2026,  945.00, 2300.00, 37.00,  945.00,  268.00),
    (2026, 2300.00,    NULL, 39.00, 2300.00,  770.00)
ON CONFLICT (agno, uvt_desde) DO NOTHING
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Procedimiento por contrato ──────────────────────────────────────────────
-- Ya se dejó `procedimiento_retefuente` y `porcentaje_fijo_retencion` en V102
-- (contrato_laboral) para no volver a alterar la tabla.

-- ── Procedimiento 2: porcentaje fijo semestral ──────────────────────────────
-- Se calcula en junio y diciembre promediando los 12 meses anteriores, y se
-- aplica todo el semestre siguiente. Es una ruta DISTINTA al procedimiento 1,
-- no un flag sobre el mismo cálculo.
CREATE TABLE IF NOT EXISTS retefuente_porcentaje_fijo (
    id           BIGSERIAL     PRIMARY KEY,
    contrato_id  BIGINT        NOT NULL REFERENCES contrato_laboral(id) ON DELETE CASCADE,
    semestre     VARCHAR(7)    NOT NULL,          -- '2026-1' | '2026-2'
    porcentaje   NUMERIC(5,2)  NOT NULL,
    base_calculo NUMERIC(15,2) NOT NULL,
    meses_promediados INT      NOT NULL DEFAULT 12,
    calculado_at TIMESTAMP     NOT NULL DEFAULT NOW(),
    traza        JSONB,
    CONSTRAINT uq_rf_fijo UNIQUE (contrato_id, semestre)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_rf_fijo_contrato ON retefuente_porcentaje_fijo(contrato_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Deducciones que depuran la base ─────────────────────────────────────────
-- Esto es lo que tiene chicha, no la tabla de rangos.
CREATE TABLE IF NOT EXISTS empleado_deduccion_renta (
    id            BIGSERIAL     PRIMARY KEY,
    empresa_id    INT           NOT NULL REFERENCES empresa(id),
    contrato_id   BIGINT        NOT NULL REFERENCES contrato_laboral(id) ON DELETE CASCADE,
    tipo          VARCHAR(30)   NOT NULL,
    valor         NUMERIC(15,2) NOT NULL DEFAULT 0,
    vigente_desde DATE          NOT NULL,
    vigente_hasta DATE,
    soporte       VARCHAR(300),                    -- referencia al documento
    created_at    TIMESTAMP     NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_edr_tipo CHECK (tipo IN (
        'DEPENDIENTES',          -- 10% del ingreso, tope 32 UVT/mes
        'INTERESES_VIVIENDA',    -- tope 100 UVT/mes
        'MEDICINA_PREPAGADA',    -- tope 16 UVT/mes
        'AFC',                   -- cuenta de ahorro para el fomento a la construcción
        'AFP_VOLUNTARIO'
    )),
    CONSTRAINT chk_edr_vigencia CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_edr_contrato ON empleado_deduccion_renta(contrato_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Conceptos de retefuente en el catálogo ──────────────────────────────────
-- No hardcodeados: van al catálogo como cualquier otra deducción.
INSERT INTO concepto_nomina
    (empresa_id, codigo, nombre, clase, constituye_ibc, base, vigente_desde, orden, codigo_dian)
VALUES
    (NULL, 'RETEFUENTE_P1', 'Retención en la fuente (proc. 1)', 'DEDUCCION', FALSE, 'MANUAL', '2026-01-01', 400, 'RetencionFuente'),
    (NULL, 'RETEFUENTE_P2', 'Retención en la fuente (proc. 2)', 'DEDUCCION', FALSE, 'MANUAL', '2026-01-01', 401, 'RetencionFuente')
ON CONFLICT (empresa_id, codigo, vigente_desde) DO NOTHING
MIG_SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_deduccion_renta');
        Schema::dropIfExists('retefuente_porcentaje_fijo');
        Schema::dropIfExists('retefuente_rango');
        Schema::dropIfExists('uvt_valor');
    }
};
