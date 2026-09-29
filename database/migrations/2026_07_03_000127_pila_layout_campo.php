<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V127 — pila layout campo.
 *
 * Traducción fiel de la migración Flyway V127__pila_layout_campo.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V127: PILA P6 — layout del archivo (dirigido por datos, cualquier operador) ─
--
-- El archivo PILA es un ESTÁNDAR del Ministerio (Anexo Técnico 2, Res. 2388/2016):
-- todos los operadores (Aportes en Línea, SOI…) aceptan el mismo layout. Lo
-- hacemos DINÁMICO: cada campo del archivo se define aquí (orden, longitud,
-- formato, relleno) y mapea a un `codigo` que el exportador resuelve del modelo.
-- Ajustar el layout al oficial no requiere recompilar.
--
-- REGLA (no inventar): este es un SUBCONJUNTO INICIAL con los campos núcleo del
-- registro tipo 2. Completar/ajustar longitudes y orden contra el Anexo Técnico
-- vigente antes de radicar en producción.

CREATE TABLE IF NOT EXISTS pila_layout_campo (
    id             BIGSERIAL   PRIMARY KEY,
    tipo_registro  VARCHAR(2)  NOT NULL,           -- '01' encabezado | '02' cotizante
    orden          INT         NOT NULL,
    codigo         VARCHAR(40) NOT NULL,           -- lo resuelve PilaArchivoExporter
    etiqueta       VARCHAR(120),
    longitud       INT,                            -- ancho fijo; NULL = sin recorte
    tipo_dato      VARCHAR(1)  NOT NULL DEFAULT 'A', -- N (numérico) | A (alfanumérico)
    relleno        VARCHAR(1),                     -- carácter de relleno
    alineacion     VARCHAR(1)  NOT NULL DEFAULT 'D', -- I (izquierda) | D (derecha)
    activo         BOOLEAN     NOT NULL DEFAULT TRUE,

    CONSTRAINT uq_pila_layout UNIQUE (tipo_registro, orden)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_pila_layout_tr ON pila_layout_campo(tipo_registro, orden)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Registro tipo 01 (encabezado) — subconjunto inicial ─────────────────────
INSERT INTO pila_layout_campo (tipo_registro, orden, codigo, etiqueta, longitud, tipo_dato, alineacion) VALUES
    ('01', 1,  'TIPO_REGISTRO',       'Tipo de registro',        2,  'N', 'D'),
    ('01', 2,  'MODALIDAD_PLANILLA',  'Modalidad de la planilla',1,  'A', 'I'),
    ('01', 3,  'TIPO_PLANILLA',       'Tipo de planilla',        1,  'A', 'I'),
    ('01', 4,  'RAZON_SOCIAL',        'Razón social',            200,'A', 'I'),
    ('01', 5,  'TIPO_DOC_APORTANTE',  'Tipo doc. aportante',     2,  'A', 'I'),
    ('01', 6,  'NUM_DOC_APORTANTE',   'Número doc. aportante',   16, 'A', 'I'),
    ('01', 7,  'DV_APORTANTE',        'Dígito de verificación',  1,  'N', 'D'),
    ('01', 8,  'TIPO_APORTANTE',      'Tipo de aportante',       2,  'A', 'I'),
    ('01', 9,  'PERIODO_PAGO_SALUD',  'Período salud',           7,  'A', 'I'),
    ('01', 10, 'PERIODO_PAGO',        'Período pensión/otros',   7,  'A', 'I'),
    ('01', 11, 'TOTAL_EMPLEADOS',     'Total cotizantes',        5,  'N', 'D')
ON CONFLICT (tipo_registro, orden) DO NOTHING
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Registro tipo 02 (cotizante) — subconjunto inicial ──────────────────────
INSERT INTO pila_layout_campo (tipo_registro, orden, codigo, etiqueta, longitud, tipo_dato, alineacion) VALUES
    ('02', 1,  'TIPO_REGISTRO',       'Tipo de registro',        2,  'N', 'D'),
    ('02', 2,  'SECUENCIA',           'Secuencia',               5,  'N', 'D'),
    ('02', 3,  'TIPO_DOCUMENTO',      'Tipo de documento',       2,  'A', 'I'),
    ('02', 4,  'NUMERO_IDENTIFICACION','Número de identificación',16,'A', 'I'),
    ('02', 5,  'TIPO_COTIZANTE',      'Tipo de cotizante',       2,  'A', 'I'),
    ('02', 6,  'SUBTIPO_COTIZANTE',   'Subtipo de cotizante',    2,  'A', 'I'),
    ('02', 7,  'APELLIDO1',           'Primer apellido',         40, 'A', 'I'),
    ('02', 8,  'APELLIDO2',           'Segundo apellido',        40, 'A', 'I'),
    ('02', 9,  'NOMBRE1',             'Primer nombre',           40, 'A', 'I'),
    ('02', 10, 'NOMBRE2',             'Segundo nombre',          40, 'A', 'I'),
    ('02', 11, 'COD_EPS',             'Código EPS',              6,  'A', 'I'),
    ('02', 12, 'COD_AFP',             'Código AFP',              6,  'A', 'I'),
    ('02', 13, 'COD_CCF',             'Código CCF',              6,  'A', 'I'),
    ('02', 14, 'COD_ARL',             'Código ARL',              6,  'A', 'I'),
    ('02', 15, 'DIAS_PENSION',        'Días pensión',            2,  'N', 'D'),
    ('02', 16, 'DIAS_SALUD',          'Días salud',              2,  'N', 'D'),
    ('02', 17, 'DIAS_ARL',            'Días ARL',                2,  'N', 'D'),
    ('02', 18, 'DIAS_CCF',            'Días CCF',                2,  'N', 'D'),
    ('02', 19, 'IBC_PENSION',         'IBC pensión',             9,  'N', 'D'),
    ('02', 20, 'IBC_SALUD',           'IBC salud',               9,  'N', 'D'),
    ('02', 21, 'IBC_ARL',             'IBC ARL',                 9,  'N', 'D'),
    ('02', 22, 'IBC_CCF',             'IBC CCF',                 9,  'N', 'D'),
    ('02', 23, 'TARIFA_PENSION',      'Tarifa pensión',          7,  'N', 'D'),
    ('02', 24, 'APORTE_PENSION',      'Aporte pensión',          9,  'N', 'D'),
    ('02', 25, 'TARIFA_SALUD',        'Tarifa salud',            7,  'N', 'D'),
    ('02', 26, 'APORTE_SALUD',        'Aporte salud',            9,  'N', 'D'),
    ('02', 27, 'TARIFA_ARL',          'Tarifa ARL',              9,  'N', 'D'),
    ('02', 28, 'APORTE_RIESGOS',      'Aporte ARL',              9,  'N', 'D'),
    ('02', 29, 'APORTE_CCF',          'Aporte CCF',              9,  'N', 'D')
ON CONFLICT (tipo_registro, orden) DO NOTHING
MIG_SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('pila_layout_campo');
    }
};
