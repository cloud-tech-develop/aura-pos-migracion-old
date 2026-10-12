<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V211 — Tercero persona natural ampliado: identidad, residencia, teléfonos y correos.
 *
 * Traducción de la migración Flyway V211__tercero_persona_natural_ampliada.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE tercero ADD COLUMN IF NOT EXISTS rut VARCHAR(20)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE tercero ADD COLUMN IF NOT EXISTS codigo_pais_expedicion VARCHAR(3)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE tercero ADD COLUMN IF NOT EXISTS estado_civil VARCHAR(20)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE tercero ADD COLUMN IF NOT EXISTS rh VARCHAR(3)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE tercero ADD COLUMN IF NOT EXISTS codigo_pais_nacimiento VARCHAR(3)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE tercero ADD COLUMN IF NOT EXISTS municipio_nacimiento_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE tercero ADD COLUMN IF NOT EXISTS zona VARCHAR(10)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE tercero ADD COLUMN IF NOT EXISTS comuna VARCHAR(80)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE tercero ADD COLUMN IF NOT EXISTS barrio VARCHAR(80)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE tercero ADD COLUMN IF NOT EXISTS contacto_nombre VARCHAR(150)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE tercero ADD COLUMN IF NOT EXISTS foto_oficial_url VARCHAR(500)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE tercero ADD COLUMN IF NOT EXISTS foto_perfil_url VARCHAR(500)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE tercero ADD COLUMN IF NOT EXISTS firma_url VARCHAR(500)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS tercero_telefono (
    id           BIGSERIAL    PRIMARY KEY,
    tercero_id   BIGINT       NOT NULL REFERENCES tercero(id) ON DELETE CASCADE,
    de_contacto  BOOLEAN      NOT NULL DEFAULT FALSE,
    tipo         VARCHAR(15)  NOT NULL DEFAULT 'CELULAR',
    numero       VARCHAR(30)  NOT NULL,
    extension    VARCHAR(10),
    orden        INTEGER      NOT NULL DEFAULT 0,
    CONSTRAINT ck_tercero_telefono_tipo CHECK (tipo IN ('CELULAR', 'CASA', 'OFICINA', 'OTRO'))
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_tercero_telefono_tercero ON tercero_telefono (tercero_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS tercero_correo (
    id           BIGSERIAL    PRIMARY KEY,
    tercero_id   BIGINT       NOT NULL REFERENCES tercero(id) ON DELETE CASCADE,
    de_contacto  BOOLEAN      NOT NULL DEFAULT FALSE,
    correo       VARCHAR(150) NOT NULL,
    orden        INTEGER      NOT NULL DEFAULT 0
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_tercero_correo_tercero ON tercero_correo (tercero_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO tercero_telefono (tercero_id, tipo, numero, orden)
SELECT t.id, 'CELULAR', TRIM(t.telefono), 0
  FROM tercero t
 WHERE t.telefono IS NOT NULL AND TRIM(t.telefono) <> ''
   AND NOT EXISTS (SELECT 1 FROM tercero_telefono x WHERE x.tercero_id = t.id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO tercero_correo (tercero_id, correo, orden)
SELECT t.id, TRIM(t.email), 0
  FROM tercero t
 WHERE t.email IS NOT NULL AND TRIM(t.email) <> ''
   AND NOT EXISTS (SELECT 1 FROM tercero_correo x WHERE x.tercero_id = t.id)
MIG_SQL);
    }

    public function down(): void
    {
        // Sin reversa: los datos migrados no se deshacen.
    }
};
