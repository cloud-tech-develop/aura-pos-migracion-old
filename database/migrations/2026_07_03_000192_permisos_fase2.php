<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V192 — Permisos, segunda etapa (fases P5–P10 de docs/PLAN_PERMISOS.md).
 *
 * Traducción de la migración Flyway V192__permisos_fase2.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 *
 * Submódulos Obligaciones y Bitácora, acciones especiales por perfil y usuario,
 * límites de descuento/precio con autorización, bitácora de auditoría,
 * "todas las sedes" en el perfil y control de sesión en el usuario.
 * El día 1 nadie gana ni pierde acceso.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
INSERT INTO submodulos (modulo_id, nombre, codigo, descripcion, activo, orden, created_at, updated_at)
SELECT m.id, 'Obligaciones', 'obligaciones',
       'Obligaciones financieras: préstamos, cuotas e intereses',
       TRUE,
       COALESCE((SELECT MAX(orden) FROM submodulos WHERE modulo_id = m.id), 0) + 1,
       now(), now()
FROM modulos m
WHERE m.codigo = 'tesoreria'
  AND NOT EXISTS (SELECT 1 FROM submodulos s WHERE s.modulo_id = m.id AND s.codigo = 'obligaciones')
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO submodulos (modulo_id, nombre, codigo, descripcion, activo, orden, created_at, updated_at)
SELECT m.id, 'Bitácora', 'bitacora',
       'Quién anuló, editó, autorizó o cambió qué y cuándo',
       TRUE,
       COALESCE((SELECT MAX(orden) FROM submodulos WHERE modulo_id = m.id), 0) + 1,
       now(), now()
FROM modulos m
WHERE m.codigo = 'caja'
  AND NOT EXISTS (SELECT 1 FROM submodulos s WHERE s.modulo_id = m.id AND s.codigo = 'bitacora')
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO empresa_submodulo (empresa_id, submodulo_id, activo, created_at, updated_at)
SELECT em.empresa_id, s.id, TRUE, now(), now()
FROM submodulos s
JOIN modulos m ON m.id = s.modulo_id
JOIN empresa_modulo em ON em.modulo_id = m.id AND em.activo = TRUE
WHERE ((m.codigo = 'tesoreria' AND s.codigo = 'obligaciones')
       OR (m.codigo = 'caja' AND s.codigo = 'bitacora'))
  AND NOT EXISTS (
      SELECT 1 FROM empresa_submodulo es
      WHERE es.empresa_id = em.empresa_id AND es.submodulo_id = s.id
  )
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO perfil_permiso (perfil_id, submodulo_id, ver, crear, editar, anular)
SELECT pp.perfil_id, nuevo.id, bool_or(pp.ver), bool_or(pp.crear), bool_or(pp.editar), bool_or(pp.anular)
FROM perfil_permiso pp
JOIN submodulos s ON s.id = pp.submodulo_id
JOIN modulos m ON m.id = s.modulo_id AND m.codigo = 'tesoreria'
JOIN submodulos nuevo ON nuevo.modulo_id = m.id AND nuevo.codigo = 'obligaciones'
WHERE s.id <> nuevo.id
  AND NOT EXISTS (SELECT 1 FROM perfil_permiso x WHERE x.perfil_id = pp.perfil_id AND x.submodulo_id = nuevo.id)
GROUP BY pp.perfil_id, nuevo.id
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS permiso_accion_especial (
    id            BIGSERIAL     PRIMARY KEY,
    submodulo_id  BIGINT        NOT NULL REFERENCES submodulos(id) ON DELETE CASCADE,
    codigo        VARCHAR(40)   NOT NULL,
    nombre        VARCHAR(120)  NOT NULL,
    descripcion   VARCHAR(300),
    hereda_de     VARCHAR(10),
    orden         INT           NOT NULL DEFAULT 0
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_permiso_accion_especial
    ON permiso_accion_especial (submodulo_id, codigo)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS perfil_accion_especial (
    id         BIGSERIAL  PRIMARY KEY,
    perfil_id  BIGINT     NOT NULL REFERENCES perfil(id) ON DELETE CASCADE,
    accion_id  BIGINT     NOT NULL REFERENCES permiso_accion_especial(id) ON DELETE CASCADE,
    permitido  BOOLEAN    NOT NULL
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_perfil_accion_especial
    ON perfil_accion_especial (perfil_id, accion_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS usuario_accion_especial (
    id          BIGSERIAL  PRIMARY KEY,
    usuario_id  INT        NOT NULL REFERENCES usuario(id) ON DELETE CASCADE,
    accion_id   BIGINT     NOT NULL REFERENCES permiso_accion_especial(id) ON DELETE CASCADE,
    permitido   BOOLEAN    NOT NULL,
    created_by  INT,
    created_at  TIMESTAMP  NOT NULL DEFAULT now()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_usuario_accion_especial
    ON usuario_accion_especial (usuario_id, accion_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO permiso_accion_especial (submodulo_id, codigo, nombre, descripcion, hereda_de, orden)
SELECT s.id, v.codigo, v.nombre, v.descripcion, v.hereda_de, v.orden
FROM (VALUES
    ('ventas', 'ventas', 'AUTORIZAR_DESCUENTO', 'Autorizar descuentos y precios',
     'Autoriza, con su usuario y PIN, descuentos o rebajas de precio por encima del límite de otro usuario', NULL, 1),
    ('ventas', 'ventas', 'VENDER_A_CREDITO', 'Vender a crédito',
     'Registrar ventas con forma de pago crédito (queda en cartera)', 'CREAR', 2),
    ('principal', 'punto-de-venta', 'VENDER_A_CREDITO', 'Vender a crédito',
     'Cobrar con forma de pago crédito en el punto de venta (queda en cartera)', 'VER', 1),
    ('contabilidad', 'periodos-contables', 'REABRIR', 'Reabrir período',
     'Reabrir un período contable cerrado', 'EDITAR', 1),
    ('inventario', 'reconteos', 'APROBAR', 'Aprobar reconteo',
     'Aprobar un reconteo y ajustar el inventario', 'EDITAR', 1),
    ('cartera', 'cartera', 'APROBAR_CREDITO', 'Aprobar solicitudes de crédito',
     'Aprobar o rechazar ventas a crédito que superan el cupo o están en mora', 'EDITAR', 1),
    ('recursos-humanos', 'liquidacion-nomina', 'APROBAR', 'Aprobar nómina',
     'Aprobar la liquidación de nómina de un período', 'EDITAR', 1),
    ('caja', 'usuarios', 'CERRAR_SESIONES', 'Cerrar sesiones',
     'Cerrar todas las sesiones abiertas de un usuario', 'EDITAR', 1)
) AS v(modulo, submodulo, codigo, nombre, descripcion, hereda_de, orden)
JOIN modulos m ON m.codigo = v.modulo
JOIN submodulos s ON s.modulo_id = m.id AND s.codigo = v.submodulo
ON CONFLICT (submodulo_id, codigo) DO NOTHING
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO perfil_accion_especial (perfil_id, accion_id, permitido)
SELECT p.id, a.id, TRUE
FROM perfil p
JOIN permiso_accion_especial a ON a.codigo = 'AUTORIZAR_DESCUENTO'
WHERE p.codigo = 'SUPERVISOR'
ON CONFLICT (perfil_id, accion_id) DO NOTHING
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE perfil ADD COLUMN IF NOT EXISTS descuento_max_pct NUMERIC(5,2)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE perfil ADD COLUMN IF NOT EXISTS rebaja_precio_max_pct NUMERIC(5,2)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS descuento_max_pct NUMERIC(5,2)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS rebaja_precio_max_pct NUMERIC(5,2)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS autorizacion (
    id              BIGSERIAL     PRIMARY KEY,
    empresa_id      INT           NOT NULL,
    solicitante_id  INT           NOT NULL,
    autorizador_id  INT           NOT NULL,
    tipo            VARCHAR(30)   NOT NULL,
    descuento_pct   NUMERIC(7,2),
    rebaja_pct      NUMERIC(7,2),
    motivo          VARCHAR(300),
    estado          VARCHAR(12)   NOT NULL DEFAULT 'VIGENTE',
    documento_tipo  VARCHAR(30),
    documento_id    BIGINT,
    expira_en       TIMESTAMP     NOT NULL,
    usada_en        TIMESTAMP,
    created_at      TIMESTAMP     NOT NULL DEFAULT now()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_autorizacion_empresa ON autorizacion (empresa_id, created_at)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS auditoria_evento (
    id              BIGSERIAL     PRIMARY KEY,
    empresa_id      INT           NOT NULL,
    usuario_id      INT,
    autorizado_por  INT,
    fecha           TIMESTAMP     NOT NULL DEFAULT now(),
    clave           VARCHAR(120),
    accion          VARCHAR(40)   NOT NULL,
    entidad         VARCHAR(60),
    entidad_id      VARCHAR(60),
    descripcion     TEXT,
    antes           TEXT,
    despues         TEXT,
    metodo          VARCHAR(10),
    ruta            VARCHAR(300),
    ip              VARCHAR(64),
    origen          VARCHAR(10)   NOT NULL DEFAULT 'SERVICIO'
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_auditoria_evento_empresa ON auditoria_evento (empresa_id, fecha)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_auditoria_evento_entidad ON auditoria_evento (empresa_id, entidad, entidad_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE perfil ADD COLUMN IF NOT EXISTS todas_sedes BOOLEAN NOT NULL DEFAULT TRUE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS token_version INT NOT NULL DEFAULT 0
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS intentos_fallidos INT NOT NULL DEFAULT 0
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS bloqueado_hasta TIMESTAMP
MIG_SQL);
    }

    public function down(): void
    {
        // Sin reversa: los cambios son aditivos.
    }
};
