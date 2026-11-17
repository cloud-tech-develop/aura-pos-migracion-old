<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V244 — Frente General también en los proyectos que ya tenían frentes; en las
 * empresas sin frentes, el General recibe los trabajadores activos y el turno.
 *
 * Traducción de la migración Flyway V244__frente_general_proyectos_existentes.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
INSERT INTO proyecto_frente (empresa_id, proyecto_id, codigo, nombre, descripcion, estado, es_general,
       created_at, updated_at)
SELECT p.empresa_id, p.id, 'GENERAL', 'General (todo el proyecto)',
       'Frente creado por el sistema para registrar el proyecto completo, sin dividirlo en frentes.',
       'ACTIVO', TRUE, now(), now()
  FROM proyecto p
 WHERE p.deleted_at IS NULL
   AND NOT EXISTS (SELECT 1 FROM proyecto_frente f
                    WHERE f.proyecto_id = p.id AND f.es_general AND f.deleted_at IS NULL)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO proyecto_frente_trabajador (empresa_id, proyecto_id, frente_id, empleado_id, fecha_inicio,
       estado, observacion, created_at, updated_at)
SELECT DISTINCT ON (g.id, t.empleado_id) t.empresa_id, t.proyecto_id, g.id, t.empleado_id, t.fecha_inicio,
       'ACTIVO', 'Pasado al proyecto completo al dejar de usar frentes.', now(), now()
  FROM proyecto_frente_trabajador t
  JOIN proyecto_frente f ON f.id = t.frente_id AND f.deleted_at IS NULL AND NOT f.es_general
  JOIN proyecto_frente g ON g.proyecto_id = t.proyecto_id AND g.es_general AND g.deleted_at IS NULL
  JOIN nomina_config c ON c.empresa_id = t.empresa_id AND NOT c.asistencia_usa_frentes
 WHERE t.estado = 'ACTIVO' AND t.deleted_at IS NULL
   AND NOT EXISTS (SELECT 1 FROM proyecto_frente_trabajador x
                    WHERE x.frente_id = g.id AND x.empleado_id = t.empleado_id
                      AND x.estado = 'ACTIVO' AND x.deleted_at IS NULL)
 ORDER BY g.id, t.empleado_id, t.fecha_inicio NULLS LAST
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO frente_turno (empresa_id, proyecto_id, frente_id, turno_id, fecha_inicio, fecha_fin,
       created_at, updated_at)
SELECT DISTINCT ON (g.id) ft.empresa_id, ft.proyecto_id, g.id, ft.turno_id, ft.fecha_inicio, ft.fecha_fin,
       now(), now()
  FROM frente_turno ft
  JOIN proyecto_frente f ON f.id = ft.frente_id AND f.deleted_at IS NULL AND NOT f.es_general
  JOIN proyecto_frente g ON g.proyecto_id = ft.proyecto_id AND g.es_general AND g.deleted_at IS NULL
  JOIN nomina_config c ON c.empresa_id = ft.empresa_id AND NOT c.asistencia_usa_frentes
 WHERE ft.deleted_at IS NULL
   AND NOT EXISTS (SELECT 1 FROM frente_turno x WHERE x.frente_id = g.id AND x.deleted_at IS NULL)
 ORDER BY g.id, ft.fecha_inicio DESC NULLS LAST, ft.id DESC
MIG_SQL);
    }

    public function down(): void
    {
        // Sin reversa: el General puede tener asistencia registrada.
    }
};
