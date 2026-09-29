<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V111 — pila.
 *
 * Traducción fiel de la migración Flyway V111__pila.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V111: Fase 6 — PILA (planilla integrada de liquidación de aportes) ──────
--
-- Confirmada como necesaria: hay clientes que pagan toda su seguridad social
-- a través del sistema. NO es cancelable.
--
-- DEPENDE DE LAS CINCO FASES ANTERIORES. No es la última por orden arbitrario:
-- consume la salida de todas.
--   · Fase 1  → identificación desagregada (4 componentes de nombre)
--   · Fase 2  → historial salarial (para la bandera `vsp`)
--   · Fase 3  → conceptos tipificados
--   · Fase 0  → IBC correcto, topes, fondo de solidaridad, exoneración
--   · Fase 5.5→ afiliaciones (sin ellas no hay a quién reportarle)
--
-- Seguro sin tocar código: solo crea tablas nuevas.

-- ── Encabezado: datos del aportante ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pila_encabezado (
    id                       BIGSERIAL     PRIMARY KEY,
    empresa_id               INT           NOT NULL REFERENCES empresa(id),
    periodo                  VARCHAR(7)    NOT NULL,   -- 'YYYY-MM'

    -- Snapshot del aportante al momento de generar. NO se regenera.
    razon_social             VARCHAR(200)  NOT NULL,
    tipo_documento           VARCHAR(5)    NOT NULL,
    numero_documento         VARCHAR(30)   NOT NULL,
    digito_verificacion      VARCHAR(2),

    tipo_aportante           VARCHAR(5),
    clase_aportante          VARCHAR(5),
    naturaleza_aportante     VARCHAR(5),
    tipo_persona             VARCHAR(5),
    forma_presentacion       VARCHAR(5),
    codigo_operador          VARCHAR(10),

    cod_sucursal             VARCHAR(20),
    nombre_sucursal          VARCHAR(150),
    direccion                VARCHAR(200),
    cod_departamento         VARCHAR(5),
    cod_ciudad               VARCHAR(5),
    cod_actividad_economica  VARCHAR(10),
    telefono                 VARCHAR(40),
    email                    VARCHAR(150),

    -- Representante legal: obligatorio, desagregado.
    rep_legal_tipo_documento VARCHAR(5),
    rep_legal_documento      VARCHAR(30),
    rep_legal_apellido1      VARCHAR(40),
    rep_legal_apellido2      VARCHAR(40),
    rep_legal_nombre1        VARCHAR(40),
    rep_legal_nombre2        VARCHAR(40),

    exonerado_ley_1607       BOOLEAN       NOT NULL DEFAULT FALSE,
    beneficiario_ley_1429    BOOLEAN       NOT NULL DEFAULT FALSE,

    estado                   VARCHAR(20)   NOT NULL DEFAULT 'BORRADOR',
    created_at               TIMESTAMP     NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_pila_enc_estado CHECK (estado IN
        ('BORRADOR', 'GENERADA', 'PRESENTADA', 'PAGADA', 'ANULADA')),
    CONSTRAINT uq_pila_enc UNIQUE (empresa_id, periodo)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Registro tipo 01: la planilla ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pila_planilla (
    id                       BIGSERIAL     PRIMARY KEY,
    pila_encabezado_id       BIGINT        NOT NULL REFERENCES pila_encabezado(id) ON DELETE CASCADE,
    tipo_registro            VARCHAR(2)    NOT NULL DEFAULT '01',
    modalidad                VARCHAR(2),
    secuencia                INT           NOT NULL DEFAULT 1,

    tipo_planilla            VARCHAR(2),   -- E=empleados, ...
    numero_planilla          VARCHAR(30),
    numero_planilla_asociada VARCHAR(30),
    fecha_pago_asociada      DATE,

    cod_arl                  VARCHAR(10),
    periodo_pago             VARCHAR(7),   -- pensión/ARL/CCF: mes vencido
    periodo_pago_salud       VARCHAR(7),   -- salud: mes anticipado. SON DISTINTOS.
    fecha_pago               DATE,

    total_empleados          INT           NOT NULL DEFAULT 0,
    total_nomina             NUMERIC(15,2) NOT NULL DEFAULT 0,

    created_at               TIMESTAMP     NOT NULL DEFAULT NOW()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_pila_planilla ON pila_planilla(pila_encabezado_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN pila_planilla.periodo_pago_salud IS
    'Salud se cotiza mes ANTICIPADO; pensión/ARL/CCF mes VENCIDO. Son períodos '
    'distintos en el mismo archivo — no unificarlos.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Registro tipo 02: una fila por cotizante (~120 columnas) ────────────────
CREATE TABLE IF NOT EXISTS pila_cotizante (
    id                    BIGSERIAL     PRIMARY KEY,
    pila_planilla_id      BIGINT        NOT NULL REFERENCES pila_planilla(id) ON DELETE CASCADE,
    contrato_id           BIGINT        REFERENCES contrato_laboral(id),  -- trazabilidad interna
    tipo_registro         VARCHAR(2)    NOT NULL DEFAULT '02',
    secuencia             INT           NOT NULL,

    -- ── Identificación (Fase 1) ─────────────────────────────────────────────
    tipo_documento        VARCHAR(5)    NOT NULL,
    numero_identificacion VARCHAR(30)   NOT NULL,
    tipo_cotizante        VARCHAR(5)    NOT NULL,
    subtipo_cotizante     VARCHAR(5),
    extranjero_no_obligado BOOLEAN      NOT NULL DEFAULT FALSE,
    colombiano_exterior   BOOLEAN       NOT NULL DEFAULT FALSE,
    cod_departamento      VARCHAR(5),
    cod_ciudad            VARCHAR(5),
    apellido1             VARCHAR(40)   NOT NULL,
    apellido2             VARCHAR(40),
    nombre1               VARCHAR(40)   NOT NULL,
    nombre2               VARCHAR(40),

    -- ── Banderas de novedad (DERIVADAS — el corazón de PILA) ────────────────
    -- No se capturan: se calculan cruzando contrato + historial salarial +
    -- afiliaciones + novedades contra el período.
    ing                   BOOLEAN NOT NULL DEFAULT FALSE,  -- ingreso
    ret                   BOOLEAN NOT NULL DEFAULT FALSE,  -- retiro
    tde                   BOOLEAN NOT NULL DEFAULT FALSE,  -- traslado desde EPS
    tae                   BOOLEAN NOT NULL DEFAULT FALSE,  -- traslado a EPS
    tdp                   BOOLEAN NOT NULL DEFAULT FALSE,  -- traslado desde pensión
    tap                   BOOLEAN NOT NULL DEFAULT FALSE,  -- traslado a pensión
    tdl                   BOOLEAN NOT NULL DEFAULT FALSE,  -- traslado desde ARL
    tal                   BOOLEAN NOT NULL DEFAULT FALSE,  -- traslado a ARL
    tie                   BOOLEAN NOT NULL DEFAULT FALSE,  -- traslado inicio empleo
    tdc                   BOOLEAN NOT NULL DEFAULT FALSE,  -- traslado desde CCF
    tac                   BOOLEAN NOT NULL DEFAULT FALSE,  -- traslado a CCF
    vsp                   BOOLEAN NOT NULL DEFAULT FALSE,  -- variación permanente salario
    correccion            BOOLEAN NOT NULL DEFAULT FALSE,
    vst                   BOOLEAN NOT NULL DEFAULT FALSE,  -- variación transitoria salario
    sln                   BOOLEAN NOT NULL DEFAULT FALSE,  -- suspensión/licencia no remunerada
    ige                   BOOLEAN NOT NULL DEFAULT FALSE,  -- incapacidad general
    lma                   BOOLEAN NOT NULL DEFAULT FALSE,  -- licencia maternidad
    vac_lr                BOOLEAN NOT NULL DEFAULT FALSE,  -- vacaciones/licencia remunerada
    avp                   BOOLEAN NOT NULL DEFAULT FALSE,  -- aporte voluntario pensión
    vct                   BOOLEAN NOT NULL DEFAULT FALSE,  -- variación centro trabajo
    irl                   BOOLEAN NOT NULL DEFAULT FALSE,  -- incapacidad riesgo laboral

    -- ── Fechas de las novedades (pares) ─────────────────────────────────────
    fecha_ingreso         DATE,
    fecha_retiro          DATE,
    fecha_inicio_vsp      DATE,
    fecha_inicio_vst      DATE,  fecha_fin_vst    DATE,
    fecha_inicio_sln      DATE,  fecha_fin_sln    DATE,
    fecha_inicio_ige      DATE,  fecha_fin_ige    DATE,
    fecha_inicio_lma      DATE,  fecha_fin_lma    DATE,
    fecha_inicio_vac_lr   DATE,  fecha_fin_vac_lr DATE,
    fecha_inicio_vct      DATE,  fecha_fin_vct    DATE,
    fecha_inicio_irl      DATE,  fecha_fin_irl    DATE,

    -- ── Entidades (Fase 5.5) — códigos LITERALES, no FK ─────────────────────
    -- Snapshot: una planilla presentada es un hecho del pasado. Si la EPS
    -- cambia de código el año que viene, esta planilla debe seguir mostrando
    -- el código con el que se radicó.
    cod_eps               VARCHAR(10),
    cod_eps_traslado      VARCHAR(10),
    cod_afp               VARCHAR(10),
    cod_afp_traslado      VARCHAR(10),
    cod_ccf               VARCHAR(10),
    cod_arl               VARCHAR(10),
    clase_riesgo          VARCHAR(2),
    centro_trabajo        VARCHAR(20),

    -- ── Días por subsistema — SON DISTINTOS ENTRE SÍ ────────────────────────
    dias_cotizados_pension INT NOT NULL DEFAULT 0,
    dias_cotizados_salud   INT NOT NULL DEFAULT 0,
    dias_cotizados_arl     INT NOT NULL DEFAULT 0,
    dias_cotizados_ccf     INT NOT NULL DEFAULT 0,

    -- ── IBC por subsistema — TAMBIÉN SON DISTINTOS ──────────────────────────
    salario_basico        NUMERIC(15,2) NOT NULL DEFAULT 0,
    salario_integral      BOOLEAN       NOT NULL DEFAULT FALSE,
    ibc_pension           NUMERIC(15,2) NOT NULL DEFAULT 0,
    ibc_salud             NUMERIC(15,2) NOT NULL DEFAULT 0,
    ibc_arl               NUMERIC(15,2) NOT NULL DEFAULT 0,
    ibc_ccf               NUMERIC(15,2) NOT NULL DEFAULT 0,
    ibc_otros_parafiscales NUMERIC(15,2) NOT NULL DEFAULT 0,

    -- ── Pensión ─────────────────────────────────────────────────────────────
    tarifa_pension              NUMERIC(6,3) NOT NULL DEFAULT 0,
    aporte_pension              NUMERIC(15,2) NOT NULL DEFAULT 0,
    aporte_voluntario_afiliado  NUMERIC(15,2) NOT NULL DEFAULT 0,
    aporte_voluntario_aportante NUMERIC(15,2) NOT NULL DEFAULT 0,
    total_pension               NUMERIC(15,2) NOT NULL DEFAULT 0,
    aporte_fondo_solidaridad    NUMERIC(15,2) NOT NULL DEFAULT 0,
    aporte_fondo_subsistencia   NUMERIC(15,2) NOT NULL DEFAULT 0,
    valor_no_retenido           NUMERIC(15,2) NOT NULL DEFAULT 0,
    indicador_tarifa_pension    VARCHAR(2),

    -- ── Salud ───────────────────────────────────────────────────────────────
    tarifa_salud          NUMERIC(6,3)  NOT NULL DEFAULT 0,
    aporte_salud          NUMERIC(15,2) NOT NULL DEFAULT 0,
    valor_upc             NUMERIC(15,2) NOT NULL DEFAULT 0,
    no_autorizacion_ige   VARCHAR(30),
    valor_ige             NUMERIC(15,2) NOT NULL DEFAULT 0,
    no_autorizacion_lma   VARCHAR(30),
    valor_lma             NUMERIC(15,2) NOT NULL DEFAULT 0,

    -- ── ARL ─────────────────────────────────────────────────────────────────
    tarifa_riesgos        NUMERIC(6,3)  NOT NULL DEFAULT 0,
    aporte_riesgos        NUMERIC(15,2) NOT NULL DEFAULT 0,

    -- ── Parafiscales ────────────────────────────────────────────────────────
    tarifa_ccf            NUMERIC(6,3)  NOT NULL DEFAULT 0,
    aporte_ccf            NUMERIC(15,2) NOT NULL DEFAULT 0,
    tarifa_sena           NUMERIC(6,3)  NOT NULL DEFAULT 0,
    aporte_sena           NUMERIC(15,2) NOT NULL DEFAULT 0,
    tarifa_icbf           NUMERIC(6,3)  NOT NULL DEFAULT 0,
    aporte_icbf           NUMERIC(15,2) NOT NULL DEFAULT 0,
    tarifa_esap           NUMERIC(6,3)  NOT NULL DEFAULT 0,
    aporte_esap           NUMERIC(15,2) NOT NULL DEFAULT 0,
    tarifa_men            NUMERIC(6,3)  NOT NULL DEFAULT 0,
    aporte_men            NUMERIC(15,2) NOT NULL DEFAULT 0,

    exonerado_ley_1607    BOOLEAN       NOT NULL DEFAULT FALSE,
    horas_laboradas       INT,

    created_at            TIMESTAMP     NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_pila_cot UNIQUE (pila_planilla_id, secuencia)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_pila_cot_planilla ON pila_cotizante(pila_planilla_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_pila_cot_contrato ON pila_cotizante(contrato_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Archivo plano generado ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pila_archivo (
    id                 BIGSERIAL     PRIMARY KEY,
    pila_encabezado_id BIGINT        NOT NULL REFERENCES pila_encabezado(id) ON DELETE CASCADE,
    nombre_archivo     VARCHAR(200)  NOT NULL,
    ruta               VARCHAR(500),
    tipo_archivo       VARCHAR(20),
    tamano_bytes       BIGINT,
    total_registros    INT,
    created_at         TIMESTAMP     NOT NULL DEFAULT NOW()
)
MIG_SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('pila_archivo');
        Schema::dropIfExists('pila_cotizante');
        Schema::dropIfExists('pila_planilla');
        Schema::dropIfExists('pila_encabezado');
    }
};
