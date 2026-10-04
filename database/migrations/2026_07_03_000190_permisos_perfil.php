<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V190 — Permisos por perfil (fase P0 de docs/PLAN_PERMISOS.md).
 *
 * Traducción de la migración Flyway V190__permisos_perfil.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 *
 * Crea perfil, perfil_permiso, usuario_permiso, permiso_cambio_log,
 * submodulos.padre_id y usuario.perfil_id; sube a la base el tercer nivel de
 * Recursos Humanos y deja a cada usuario con el perfil de su rol (nadie cambia
 * de acceso con esta migración). Los submódulos de RRHH no se borran en down().
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE submodulos ADD COLUMN IF NOT EXISTS padre_id BIGINT
    REFERENCES submodulos(id) ON DELETE SET NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_submodulos_padre ON submodulos (padre_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS perfil (
    id            BIGSERIAL     PRIMARY KEY,
    empresa_id    INT           NOT NULL,
    codigo        VARCHAR(30),
    nombre        VARCHAR(80)   NOT NULL,
    descripcion   VARCHAR(300),
    acceso_total  BOOLEAN       NOT NULL DEFAULT FALSE,
    es_sistema    BOOLEAN       NOT NULL DEFAULT FALSE,
    activo        BOOLEAN       NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP     NOT NULL DEFAULT now(),
    updated_at    TIMESTAMP     NOT NULL DEFAULT now()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_perfil_empresa_nombre ON perfil (empresa_id, nombre)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_perfil_empresa_codigo ON perfil (empresa_id, codigo)
    WHERE codigo IS NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS perfil_permiso (
    id            BIGSERIAL  PRIMARY KEY,
    perfil_id     BIGINT     NOT NULL REFERENCES perfil(id) ON DELETE CASCADE,
    submodulo_id  BIGINT     NOT NULL REFERENCES submodulos(id) ON DELETE CASCADE,
    ver           BOOLEAN    NOT NULL DEFAULT FALSE,
    crear         BOOLEAN    NOT NULL DEFAULT FALSE,
    editar        BOOLEAN    NOT NULL DEFAULT FALSE,
    anular        BOOLEAN    NOT NULL DEFAULT FALSE
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_perfil_permiso ON perfil_permiso (perfil_id, submodulo_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS perfil_id BIGINT
    REFERENCES perfil(id) ON DELETE SET NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS usuario_permiso (
    id            BIGSERIAL  PRIMARY KEY,
    usuario_id    INT        NOT NULL REFERENCES usuario(id) ON DELETE CASCADE,
    submodulo_id  BIGINT     NOT NULL REFERENCES submodulos(id) ON DELETE CASCADE,
    ver           BOOLEAN,
    crear         BOOLEAN,
    editar        BOOLEAN,
    anular        BOOLEAN,
    created_by    INT,
    created_at    TIMESTAMP  NOT NULL DEFAULT now(),
    updated_at    TIMESTAMP  NOT NULL DEFAULT now()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_usuario_permiso ON usuario_permiso (usuario_id, submodulo_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS permiso_cambio_log (
    id                   BIGSERIAL    PRIMARY KEY,
    empresa_id           INT          NOT NULL,
    usuario_id           INT,
    tipo                 VARCHAR(20)  NOT NULL,
    perfil_id            BIGINT,
    usuario_afectado_id  INT,
    detalle              TEXT,
    created_at           TIMESTAMP    NOT NULL DEFAULT now()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_permiso_cambio_log_empresa ON permiso_cambio_log (empresa_id, created_at)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
WITH rrhh AS (
    SELECT id FROM modulos WHERE codigo = 'recursos-humanos'
), grupos(codigo, nombre, orden) AS (
    VALUES ('gestion', 'Gestión', 1), ('asistencia', 'Asistencia', 2), ('parametros', 'Parámetros', 3)
)
INSERT INTO submodulos (modulo_id, nombre, codigo, descripcion, activo, orden, created_at, updated_at)
SELECT rrhh.id, g.nombre, g.codigo, 'Grupo de Recursos Humanos', TRUE, g.orden, now(), now()
FROM rrhh CROSS JOIN grupos g
WHERE NOT EXISTS (SELECT 1 FROM submodulos s WHERE s.modulo_id = rrhh.id AND s.codigo = g.codigo)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
WITH rrhh AS (
    SELECT id FROM modulos WHERE codigo = 'recursos-humanos'
), hojas(codigo, nombre, padre, orden) AS (
    VALUES
        ('empleados',                  'Empleados',                   'gestion',    1),
        ('saldos-iniciales',           'Saldos iniciales',            'gestion',    2),
        ('proyectos-y-frentes',        'Proyectos y Frentes',         'gestion',    3),
        ('conceptos',                  'Conceptos',                   'gestion',    4),
        ('periodos',                   'Períodos',                    'gestion',    5),
        ('liquidacion-nomina',         'Liquidación Nómina',          'gestion',    6),
        ('preliquidacion-auditoria',   'Preliquidación / Auditoría',  'gestion',    7),
        ('nomina-electronica',         'Nómina Electrónica',          'gestion',    8),
        ('pila',                       'PILA',                        'gestion',    9),
        ('prestaciones',               'Prestaciones',                'gestion',   10),
        ('comisiones',                 'Comisiones',                  'gestion',   11),
        ('liquidar-comisiones',        'Liquidar Comisiones',         'gestion',   12),
        ('digitacion-asistencia',      'Digitación Asistencia',       'asistencia', 1),
        ('revision-asistencia-frente', 'Revisión Asistencia (Frente)','asistencia', 2),
        ('preliquidacion-frente',      'Preliquidación (Frente)',     'asistencia', 3),
        ('turnos-empleado',            'Turnos Empleado',             'asistencia', 4),
        ('marcaje',                    'Marcaje',                     'asistencia', 5),
        ('revision-asistencia',        'Revisión Asistencia',         'asistencia', 6),
        ('novedades-asistencia',       'Novedades Asistencia',        'asistencia', 7),
        ('autorizaciones',             'Autorizaciones',              'asistencia', 8),
        ('configuracion-laboral',      'Configuración Laboral',       'parametros', 1),
        ('calendario-laboral',         'Calendario Laboral',          'parametros', 2),
        ('config-nomina',              'Config. Nómina',              'parametros', 3)
)
INSERT INTO submodulos (modulo_id, nombre, codigo, descripcion, activo, orden, created_at, updated_at)
SELECT rrhh.id, h.nombre, h.codigo, NULL, TRUE, h.orden, now(), now()
FROM rrhh CROSS JOIN hojas h
WHERE NOT EXISTS (SELECT 1 FROM submodulos s WHERE s.modulo_id = rrhh.id AND s.codigo = h.codigo)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
WITH rrhh AS (
    SELECT id FROM modulos WHERE codigo = 'recursos-humanos'
), hojas(codigo, padre, orden) AS (
    VALUES
        ('empleados', 'gestion', 1), ('saldos-iniciales', 'gestion', 2), ('proyectos-y-frentes', 'gestion', 3),
        ('conceptos', 'gestion', 4), ('periodos', 'gestion', 5), ('liquidacion-nomina', 'gestion', 6),
        ('preliquidacion-auditoria', 'gestion', 7), ('nomina-electronica', 'gestion', 8), ('pila', 'gestion', 9),
        ('prestaciones', 'gestion', 10), ('comisiones', 'gestion', 11), ('liquidar-comisiones', 'gestion', 12),
        ('digitacion-asistencia', 'asistencia', 1), ('revision-asistencia-frente', 'asistencia', 2),
        ('preliquidacion-frente', 'asistencia', 3), ('turnos-empleado', 'asistencia', 4), ('marcaje', 'asistencia', 5),
        ('revision-asistencia', 'asistencia', 6), ('novedades-asistencia', 'asistencia', 7),
        ('autorizaciones', 'asistencia', 8),
        ('configuracion-laboral', 'parametros', 1), ('calendario-laboral', 'parametros', 2),
        ('config-nomina', 'parametros', 3)
)
UPDATE submodulos s
SET padre_id = p.id, orden = h.orden, updated_at = now()
FROM rrhh, hojas h, submodulos p
WHERE s.modulo_id = rrhh.id AND s.codigo = h.codigo
  AND p.modulo_id = rrhh.id AND p.codigo = h.padre
  AND s.padre_id IS DISTINCT FROM p.id
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO empresa_submodulo (empresa_id, submodulo_id, activo, created_at, updated_at)
SELECT em.empresa_id, s.id, TRUE, now(), now()
FROM submodulos s
JOIN modulos m ON m.id = s.modulo_id AND m.codigo = 'recursos-humanos'
JOIN empresa_modulo em ON em.modulo_id = m.id AND em.activo = TRUE
WHERE (s.padre_id IS NOT NULL OR s.codigo IN ('gestion', 'asistencia', 'parametros'))
  AND NOT EXISTS (
      SELECT 1 FROM empresa_submodulo es
      WHERE es.empresa_id = em.empresa_id AND es.submodulo_id = s.id
  )
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO perfil (empresa_id, codigo, nombre, descripcion, acceso_total, es_sistema)
SELECT e.id, v.codigo, v.nombre, v.descripcion, v.total, TRUE
FROM empresa e
CROSS JOIN (VALUES
    ('ADMINISTRADOR', 'Administrador', 'Todo lo que la empresa tiene activo', TRUE),
    ('CAJERO',        'Cajero',        'Punto de venta, ventas, cotizaciones, devoluciones, comprobantes y turnos', FALSE),
    ('VENDEDOR',      'Vendedor',      'Punto de venta, mi perfil, escanear QR y turnos', FALSE),
    ('SUPERVISOR',    'Supervisor',    'Punto de venta y turnos', FALSE),
    ('BASICO',        'Básico',        'Punto de venta y turnos (roles que no son uno de los fijos)', FALSE)
) AS v(codigo, nombre, descripcion, total)
WHERE NOT EXISTS (SELECT 1 FROM perfil p WHERE p.empresa_id = e.id AND p.codigo = v.codigo)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO perfil_permiso (perfil_id, submodulo_id, ver, crear, editar, anular)
SELECT p.id, s.id, TRUE, TRUE, TRUE, TRUE
FROM perfil p
JOIN (VALUES
    ('CAJERO', 'principal', 'punto-de-venta'),
    ('CAJERO', 'ventas', 'ventas'),
    ('CAJERO', 'ventas', 'cotizaciones'),
    ('CAJERO', 'ventas', 'devoluciones'),
    ('CAJERO', 'caja', 'comprobantes'),
    ('CAJERO', 'caja', 'turnos'),
    ('VENDEDOR', 'principal', 'punto-de-venta'),
    ('VENDEDOR', 'vendedores', 'mi-perfil'),
    ('VENDEDOR', 'vendedores', 'escanear-qr'),
    ('VENDEDOR', 'caja', 'turnos'),
    ('SUPERVISOR', 'principal', 'punto-de-venta'),
    ('SUPERVISOR', 'caja', 'turnos'),
    ('BASICO', 'principal', 'punto-de-venta'),
    ('BASICO', 'caja', 'turnos')
) AS v(perfil, modulo, submodulo) ON v.perfil = p.codigo
JOIN modulos m ON m.codigo = v.modulo
JOIN submodulos s ON s.modulo_id = m.id AND s.codigo = v.submodulo
WHERE p.es_sistema = TRUE
ON CONFLICT (perfil_id, submodulo_id) DO NOTHING
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE usuario u
SET perfil_id = p.id
FROM perfil p
WHERE u.perfil_id IS NULL
  AND u.rol IS DISTINCT FROM 'PLATFORM_ADMIN'
  AND p.empresa_id = u.empresa_id
  AND p.codigo = CASE u.rol
        WHEN 'SUPER_ADMIN' THEN 'ADMINISTRADOR'
        WHEN 'ADMIN'       THEN 'ADMINISTRADOR'
        WHEN 'CAJERO'      THEN 'CAJERO'
        WHEN 'VENDEDOR'    THEN 'VENDEDOR'
        WHEN 'SUPERVISOR'  THEN 'SUPERVISOR'
        ELSE 'BASICO'
    END
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE usuario DROP COLUMN IF EXISTS perfil_id');
        DB::statement('DROP TABLE IF EXISTS permiso_cambio_log');
        DB::statement('DROP TABLE IF EXISTS usuario_permiso');
        DB::statement('DROP TABLE IF EXISTS perfil_permiso');
        DB::statement('DROP TABLE IF EXISTS perfil');
    }
};
