<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V241 — Frente "General" automático para trabajar por proyecto sin frentes.
 *
 * Traducción de la migración Flyway V241__proyecto_frente_general.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE proyecto_frente ADD COLUMN IF NOT EXISTS es_general BOOLEAN NOT NULL DEFAULT FALSE');

        DB::statement(<<<'MIG_SQL'
INSERT INTO proyecto_frente (empresa_id, proyecto_id, codigo, nombre, descripcion, estado, es_general,
       created_at, updated_at)
SELECT p.empresa_id, p.id, 'GENERAL', 'General (todo el proyecto)',
       'Frente creado por el sistema para registrar el proyecto completo, sin dividirlo en frentes.',
       'ACTIVO', TRUE, now(), now()
  FROM proyecto p
 WHERE p.deleted_at IS NULL
   AND NOT EXISTS (SELECT 1 FROM proyecto_frente f WHERE f.proyecto_id = p.id AND f.deleted_at IS NULL)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS ux_frente_general_proyecto
    ON proyecto_frente (proyecto_id) WHERE es_general AND deleted_at IS NULL
MIG_SQL);
    }

    public function down(): void
    {
        // Sin reversa: los frentes GENERAL pueden tener asistencia registrada.
    }
};
