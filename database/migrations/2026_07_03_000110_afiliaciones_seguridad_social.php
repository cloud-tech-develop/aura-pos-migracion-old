<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V110 — afiliaciones seguridad social.
 *
 * Traducción fiel de la migración Flyway V110__afiliaciones_seguridad_social.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V110: Fase 5.5 — afiliaciones a seguridad social ────────────────────────
--
-- SIN ESTO PILA NO ES CONSTRUIBLE. Hoy no existe modelo de afiliaciones:
-- no hay EPS, AFP ni caja de compensación en ninguna tabla, y `empleado_arl`
-- guarda un `porcentaje` sin decir A QUÉ ARL está afiliado el empleado.
--
-- DISEÑO: son TERCEROS CON ROL, no un catálogo aislado.
-- A esas entidades se les PAGA: hay que girarles desde tesorería, el asiento
-- necesita su NIT, y exógena las reporta. Un catálogo que no sea `tercero`
-- obliga a duplicarlas como tercero el día del pago de aportes.
--
-- PERO: `tercero` es multi-tenant (empresa_id) y los códigos de PILA son
-- NACIONALES. Si cada empresa digita el código, divergen y el archivo se
-- rechaza. Por eso son LAS DOS COSAS:
--
--   entidad_seguridad_social  ← GLOBAL, solo códigos, solo lectura
--           ▲
--   tercero (por empresa, con rol EPS/AFP/CCF/ARL)
--           ▲
--   contrato_afiliacion  ← apunta a tercero, no a entidad
--
-- Seguro sin tocar código: crea tablas y agrega columnas nullable.

-- ── Catálogo NACIONAL de códigos. Sin empresa_id: lo mantenemos nosotros. ───
CREATE TABLE IF NOT EXISTS entidad_seguridad_social (
    id             BIGSERIAL    PRIMARY KEY,
    tipo           VARCHAR(10)  NOT NULL,
    codigo_oficial VARCHAR(20)  NOT NULL,   -- el que exige el operador de PILA
    nit            VARCHAR(30)  NOT NULL,
    nombre         VARCHAR(150) NOT NULL,
    activo         BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at     TIMESTAMP    NOT NULL DEFAULT NOW(),
    updated_at     TIMESTAMP,
    CONSTRAINT chk_ess_tipo  CHECK (tipo IN ('EPS', 'AFP', 'CCF', 'ARL')),
    CONSTRAINT uq_ess_codigo UNIQUE (tipo, codigo_oficial)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_ess_tipo ON entidad_seguridad_social(tipo) WHERE activo = TRUE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON TABLE entidad_seguridad_social IS
    'Catálogo NACIONAL de EPS/AFP/CCF/ARL con su código oficial del operador de '
    'PILA. Sin empresa_id: lo mantiene el proveedor, no el cliente. El cliente '
    'ELIGE DE UNA LISTA, no digita el código — así no divergen entre empresas. '
    'CAMBIA (fusiones, liquidaciones): necesita mantenimiento, no es seed único.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Enlace del tercero local (por empresa) al catálogo nacional ─────────────
ALTER TABLE tercero
    ADD COLUMN IF NOT EXISTS entidad_seguridad_social_id BIGINT
        REFERENCES entidad_seguridad_social(id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN tercero.entidad_seguridad_social_id IS
    'Para terceros con rol EPS/AFP/CCF/ARL: enlace al catálogo nacional. '
    'De aquí sale el código oficial para PILA.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Afiliación vigente por contrato ─────────────────────────────────────────
-- Apunta a TERCERO (así pagos, asientos y exógena funcionan).
-- Las FECHAS NO SON DECORATIVAS: las banderas de traslado de PILA
-- (tde, tae, tdp, tap) se DERIVAN de detectar un cambio de entidad dentro
-- del período. Sin historial de afiliación, no se pueden calcular.
CREATE TABLE IF NOT EXISTS contrato_afiliacion (
    id          BIGSERIAL   PRIMARY KEY,
    contrato_id BIGINT      NOT NULL REFERENCES contrato_laboral(id) ON DELETE CASCADE,
    tercero_id  BIGINT      NOT NULL REFERENCES tercero(id),
    tipo        VARCHAR(10) NOT NULL,
    fecha_desde DATE        NOT NULL,
    fecha_hasta DATE,                       -- NULL = vigente
    created_at  TIMESTAMP   NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_afil_tipo   CHECK (tipo IN ('EPS', 'AFP', 'CCF', 'ARL')),
    CONSTRAINT chk_afil_fechas CHECK (fecha_hasta IS NULL OR fecha_hasta >= fecha_desde)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_contrato_afiliacion ON contrato_afiliacion(contrato_id, tipo)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Una sola afiliación vigente por (contrato, tipo)
CREATE UNIQUE INDEX IF NOT EXISTS ux_afiliacion_vigente
    ON contrato_afiliacion(contrato_id, tipo) WHERE fecha_hasta IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Tipo de cotizante UGPP + centro de trabajo ──────────────────────────────
ALTER TABLE contrato_laboral
    ADD COLUMN IF NOT EXISTS tipo_cotizante    VARCHAR(5),
    ADD COLUMN IF NOT EXISTS subtipo_cotizante VARCHAR(5),
    ADD COLUMN IF NOT EXISTS centro_trabajo_id BIGINT,
    -- Nivel de riesgo ARL. Migrado desde empleado_arl.
    -- ⚠️ Pendiente #8: ¿la tarifa se resuelve por contrato o por centro de
    --    trabajo? Si es por centro de trabajo, esto se mueve allá.
    ADD COLUMN IF NOT EXISTS nivel_riesgo_arl  INT,
    ADD COLUMN IF NOT EXISTS tarifa_arl        NUMERIC(5,3)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN contrato_laboral.tipo_cotizante IS
    'Código UGPP del tipo de cotizante (01=dependiente, 12=aprendiz lectiva, ...). '
    'Requerido por PILA registro tipo 02.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Centros de trabajo (tarifa ARL por sede) ────────────────────────────────
CREATE TABLE IF NOT EXISTS centro_trabajo (
    id           BIGSERIAL     PRIMARY KEY,
    empresa_id   INT           NOT NULL REFERENCES empresa(id),
    codigo       VARCHAR(20)   NOT NULL,
    nombre       VARCHAR(150)  NOT NULL,
    nivel_riesgo INT           NOT NULL DEFAULT 1,
    tarifa_arl   NUMERIC(5,3)  NOT NULL DEFAULT 0.522,
    activo       BOOLEAN       NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_ct_nivel  CHECK (nivel_riesgo BETWEEN 1 AND 5),
    CONSTRAINT uq_ct_codigo  UNIQUE (empresa_id, codigo)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Migración de empleado_arl ───────────────────────────────────────────────
-- Se copia el nivel y la tarifa al contrato.
--
-- ⚠️ NO SE PUEDE INFERIR LA ENTIDAD: `empleado_arl` solo tiene nivel_riesgo y
--    porcentaje — NO dice a qué ARL está afiliado. Ese dato hay que CAPTURARLO
--    con cada cliente. No hay backfill posible.
UPDATE contrato_laboral c
   SET nivel_riesgo_arl = a.nivel_riesgo,
       tarifa_arl       = a.porcentaje
  FROM empleado_arl a
 WHERE a.empleado_id = c.empleado_id
   AND c.nivel_riesgo_arl IS NULL
MIG_SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('centro_trabajo');
        Schema::dropIfExists('contrato_afiliacion');
        Schema::dropIfExists('entidad_seguridad_social');
    }
};
