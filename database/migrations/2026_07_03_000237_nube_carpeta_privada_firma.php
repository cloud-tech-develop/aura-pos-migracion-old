<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V237 — Carpeta privada de UNA firma: nube_carpeta.privada_firma_id.
 *
 * Traducción de la migración Flyway V237__nube_carpeta_privada_firma.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE nube_carpeta ADD COLUMN IF NOT EXISTS privada_firma_id INT');

        DB::statement(<<<'MIG_SQL'
UPDATE nube_carpeta SET privada_firma_id = creado_por_firma_id
 WHERE visibilidad = 'SOLO_FIRMA' AND privada_firma_id IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE nube_carpeta SET visibilidad = 'COMPARTIDA'
 WHERE visibilidad = 'SOLO_FIRMA' AND privada_firma_id IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE nube_carpeta SET privada_firma_id = NULL
 WHERE visibilidad = 'COMPARTIDA' AND privada_firma_id IS NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_nube_carpeta_privada_tiene_firma') THEN
        ALTER TABLE nube_carpeta
            ADD CONSTRAINT ck_nube_carpeta_privada_tiene_firma
            CHECK ((visibilidad = 'SOLO_FIRMA') = (privada_firma_id IS NOT NULL));
    END IF;
END $$
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_nube_carpeta_privada
    ON nube_carpeta (empresa_id, privada_firma_id) WHERE privada_firma_id IS NOT NULL
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_nube_carpeta_privada');
        DB::statement('ALTER TABLE nube_carpeta DROP CONSTRAINT IF EXISTS ck_nube_carpeta_privada_tiene_firma');
        DB::statement('ALTER TABLE nube_carpeta DROP COLUMN IF EXISTS privada_firma_id');
    }
};
