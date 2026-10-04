<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V191 — Permisos: submódulo Perfiles y Permisos y registro de bloqueos (P1/P3).
 *
 * Traducción de la migración Flyway V191__permisos_pantalla_y_registro.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 *
 * Agrega caja.perfiles (activo en empresas con Caja) y permiso_bloqueo_log,
 * donde el modo OBSERVAR deja lo que habría bloqueado.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
INSERT INTO submodulos (modulo_id, nombre, codigo, descripcion, activo, orden, created_at, updated_at)
SELECT m.id, 'Perfiles y Permisos', 'perfiles',
       'Perfiles de permisos de la empresa: qué ve y qué puede hacer cada usuario',
       TRUE,
       COALESCE((SELECT MAX(orden) FROM submodulos WHERE modulo_id = m.id), 0) + 1,
       now(), now()
FROM modulos m
WHERE m.codigo = 'caja'
  AND NOT EXISTS (SELECT 1 FROM submodulos s WHERE s.modulo_id = m.id AND s.codigo = 'perfiles')
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO empresa_submodulo (empresa_id, submodulo_id, activo, created_at, updated_at)
SELECT em.empresa_id, s.id, TRUE, now(), now()
FROM submodulos s
JOIN modulos m ON m.id = s.modulo_id AND m.codigo = 'caja'
JOIN empresa_modulo em ON em.modulo_id = m.id AND em.activo = TRUE
WHERE s.codigo = 'perfiles'
  AND NOT EXISTS (
      SELECT 1 FROM empresa_submodulo es
      WHERE es.empresa_id = em.empresa_id AND es.submodulo_id = s.id
  )
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS permiso_bloqueo_log (
    id            BIGSERIAL     PRIMARY KEY,
    empresa_id    INT,
    usuario_id    INT           NOT NULL,
    modo          VARCHAR(10)   NOT NULL,
    metodo        VARCHAR(10)   NOT NULL,
    ruta          VARCHAR(300)  NOT NULL,
    clave         VARCHAR(120),
    accion        VARCHAR(10),
    fecha         DATE          NOT NULL DEFAULT CURRENT_DATE,
    veces         INT           NOT NULL DEFAULT 1,
    primera_vez   TIMESTAMP     NOT NULL DEFAULT now(),
    ultima_vez    TIMESTAMP     NOT NULL DEFAULT now()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_permiso_bloqueo_log
    ON permiso_bloqueo_log (usuario_id, metodo, ruta, fecha, modo)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_permiso_bloqueo_log_empresa
    ON permiso_bloqueo_log (empresa_id, fecha)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS permiso_bloqueo_log');
    }
};
