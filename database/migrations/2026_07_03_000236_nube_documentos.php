<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V236 — Nube de documentos por empresa (nube_espacio, nube_carpeta, nube_archivo,
 * nube_evento inmutable, nube_solicitud_espacio).
 *
 * Traducción de la migración Flyway V236__nube_documentos.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nube_espacio (
    empresa_id          INT          PRIMARY KEY REFERENCES empresa(id),
    cuota_mb            INT          NOT NULL DEFAULT 500 CHECK (cuota_mb > 0),
    max_archivos        INT          NOT NULL DEFAULT 5000 CHECK (max_archivos > 0),
    terminos_version    VARCHAR(10),
    terminos_aceptados_por INT,
    terminos_aceptados_at  TIMESTAMP,
    created_at          TIMESTAMP    NOT NULL DEFAULT now(),
    updated_at          TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nube_carpeta (
    id                  BIGSERIAL    PRIMARY KEY,
    empresa_id          INT          NOT NULL REFERENCES empresa(id),
    padre_id            BIGINT,
    nombre              VARCHAR(150) NOT NULL,
    visibilidad         VARCHAR(15)  NOT NULL DEFAULT 'COMPARTIDA'
                        CHECK (visibilidad IN ('COMPARTIDA', 'SOLO_FIRMA')),
    nivel               SMALLINT     NOT NULL DEFAULT 1 CHECK (nivel BETWEEN 1 AND 6),
    creado_por          INT          NOT NULL,
    creado_por_firma_id INT,
    created_at          TIMESTAMP    NOT NULL DEFAULT now(),
    updated_at          TIMESTAMP,
    deleted_at          TIMESTAMP,
    eliminado_por       INT,
    purgar_en           TIMESTAMP,
    CONSTRAINT uq_nube_carpeta_id_empresa UNIQUE (id, empresa_id),
    CONSTRAINT ck_nube_carpeta_no_es_su_padre CHECK (padre_id IS NULL OR padre_id <> id)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_nube_carpeta_padre_misma_empresa') THEN
        ALTER TABLE nube_carpeta
            ADD CONSTRAINT fk_nube_carpeta_padre_misma_empresa
            FOREIGN KEY (padre_id, empresa_id) REFERENCES nube_carpeta (id, empresa_id);
    END IF;
END $$
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_nube_carpeta_nombre
    ON nube_carpeta (empresa_id, COALESCE(padre_id, 0), lower(nombre))
    WHERE deleted_at IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_nube_carpeta_padre ON nube_carpeta (empresa_id, padre_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_nube_carpeta_purga ON nube_carpeta (purgar_en) WHERE purgar_en IS NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nube_archivo (
    id                  BIGSERIAL    PRIMARY KEY,
    empresa_id          INT          NOT NULL REFERENCES empresa(id),
    carpeta_id          BIGINT,
    nombre              VARCHAR(150) NOT NULL,
    extension           VARCHAR(10)  NOT NULL,
    mime                VARCHAR(100) NOT NULL,
    tamano_bytes        INT          NOT NULL CHECK (tamano_bytes > 0 AND tamano_bytes <= 1048576),
    sha256              VARCHAR(64)  NOT NULL,
    object_key          VARCHAR(120) NOT NULL,
    iv                  BYTEA        NOT NULL,
    version_llave       SMALLINT     NOT NULL,
    subido_por          INT          NOT NULL,
    subido_por_firma_id INT,
    created_at          TIMESTAMP    NOT NULL DEFAULT now(),
    updated_at          TIMESTAMP,
    deleted_at          TIMESTAMP,
    eliminado_por       INT,
    purgar_en           TIMESTAMP,
    CONSTRAINT uq_nube_archivo_object_key UNIQUE (object_key)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_nube_archivo_carpeta_misma_empresa') THEN
        ALTER TABLE nube_archivo
            ADD CONSTRAINT fk_nube_archivo_carpeta_misma_empresa
            FOREIGN KEY (carpeta_id, empresa_id) REFERENCES nube_carpeta (id, empresa_id);
    END IF;
END $$
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_nube_archivo_nombre
    ON nube_archivo (empresa_id, COALESCE(carpeta_id, 0), lower(nombre))
    WHERE deleted_at IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_nube_archivo_carpeta ON nube_archivo (empresa_id, carpeta_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_nube_archivo_purga ON nube_archivo (purgar_en) WHERE purgar_en IS NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nube_evento (
    id                  BIGSERIAL    PRIMARY KEY,
    empresa_id          INT          NOT NULL,
    usuario_id          INT          NOT NULL,
    firma_empresa_id    INT,
    accion              VARCHAR(20)  NOT NULL,
    archivo_id          BIGINT,
    carpeta_id          BIGINT,
    detalle             JSONB,
    ip                  VARCHAR(45),
    user_agent          VARCHAR(255),
    created_at          TIMESTAMP    NOT NULL DEFAULT now()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_nube_evento_empresa ON nube_evento (empresa_id, created_at DESC)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_nube_evento_archivo ON nube_evento (archivo_id) WHERE archivo_id IS NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE OR REPLACE FUNCTION nube_evento_inmutable() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    RAISE EXCEPTION 'nube_evento es una bitácora: no se puede modificar ni borrar';
END $$
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
DROP TRIGGER IF EXISTS trg_nube_evento_inmutable ON nube_evento
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TRIGGER trg_nube_evento_inmutable
    BEFORE UPDATE OR DELETE ON nube_evento
    FOR EACH ROW EXECUTE FUNCTION nube_evento_inmutable()
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nube_solicitud_espacio (
    id                  BIGSERIAL    PRIMARY KEY,
    empresa_id          INT          NOT NULL REFERENCES empresa(id),
    solicitado_por      INT          NOT NULL,
    cuota_actual_mb     INT          NOT NULL,
    cuota_solicitada_mb INT          NOT NULL CHECK (cuota_solicitada_mb > 0),
    estado              VARCHAR(15)  NOT NULL DEFAULT 'PENDIENTE'
                        CHECK (estado IN ('PENDIENTE', 'APROBADA', 'RECHAZADA')),
    atendido_por        INT,
    atendido_at         TIMESTAMP,
    observacion         VARCHAR(300),
    created_at          TIMESTAMP    NOT NULL DEFAULT now()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_nube_solicitud_pendiente
    ON nube_solicitud_espacio (empresa_id) WHERE estado = 'PENDIENTE'
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS nube_solicitud_espacio');
        DB::statement('DROP TABLE IF EXISTS nube_evento');
        DB::statement('DROP FUNCTION IF EXISTS nube_evento_inmutable()');
        DB::statement('DROP TABLE IF EXISTS nube_archivo');
        DB::statement('DROP TABLE IF EXISTS nube_carpeta');
        DB::statement('DROP TABLE IF EXISTS nube_espacio');
    }
};
