<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V174 — Notas contables: clasificación, reversión, soportes y plantillas.
 *
 * La nota reversada SIGUE CONTABILIZADA (los informes suman original y
 * reversión y netean en cero). Soportes adjuntos por nota y plantillas
 * (opcionalmente recurrentes: dejan un BORRADOR cada mes).
 *
 * Traducción de la migración Flyway V174__notas_contables_avanzadas.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE asiento_contable ADD COLUMN IF NOT EXISTS clasificacion VARCHAR(30)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE asiento_contable ADD COLUMN IF NOT EXISTS reversa_de_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE asiento_contable ADD COLUMN IF NOT EXISTS revertido_por_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE asiento_contable ADD COLUMN IF NOT EXISTS reversion_automatica BOOLEAN NOT NULL DEFAULT FALSE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE asiento_contable ADD COLUMN IF NOT EXISTS plantilla_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS ux_asiento_reversa_de
    ON asiento_contable (reversa_de_id)
    WHERE reversa_de_id IS NOT NULL AND estado <> 'ANULADO'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nota_diario_soporte (
    id              BIGSERIAL     PRIMARY KEY,
    empresa_id      INTEGER       NOT NULL,
    asiento_id      BIGINT        NOT NULL REFERENCES asiento_contable(id) ON DELETE CASCADE,
    nombre_archivo  VARCHAR(255)  NOT NULL,
    archivo_url     VARCHAR(1000) NOT NULL,
    content_type    VARCHAR(100),
    tamano_bytes    BIGINT,
    usuario_id      INTEGER,
    created_at      TIMESTAMP     NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS ix_nota_diario_soporte_asiento
    ON nota_diario_soporte (asiento_id) WHERE deleted_at IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nota_diario_plantilla (
    id              BIGSERIAL     PRIMARY KEY,
    empresa_id      INTEGER       NOT NULL,
    nombre          VARCHAR(150)  NOT NULL,
    descripcion     VARCHAR(500)  NOT NULL,
    clasificacion   VARCHAR(30),
    recurrente      BOOLEAN       NOT NULL DEFAULT FALSE,
    dia_mes         SMALLINT,
    ultimo_periodo  VARCHAR(7),
    activa          BOOLEAN       NOT NULL DEFAULT TRUE,
    usuario_id      INTEGER,
    created_at      TIMESTAMP     NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP,
    deleted_at      TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS ix_nota_diario_plantilla_empresa
    ON nota_diario_plantilla (empresa_id) WHERE deleted_at IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nota_diario_plantilla_linea (
    id               BIGSERIAL     PRIMARY KEY,
    plantilla_id     BIGINT        NOT NULL REFERENCES nota_diario_plantilla(id) ON DELETE CASCADE,
    orden            INTEGER       NOT NULL DEFAULT 0,
    cuenta_id        BIGINT        NOT NULL,
    descripcion      VARCHAR(300),
    debito           NUMERIC(18,2) NOT NULL DEFAULT 0,
    credito          NUMERIC(18,2) NOT NULL DEFAULT 0,
    tercero_id       BIGINT,
    centro_costo_id  BIGINT,
    proyecto_id      BIGINT,
    frente_id        BIGINT
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS ix_nota_diario_plantilla_linea_plantilla
    ON nota_diario_plantilla_linea (plantilla_id)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS nota_diario_plantilla_linea');
        DB::statement('DROP TABLE IF EXISTS nota_diario_plantilla');
        DB::statement('DROP TABLE IF EXISTS nota_diario_soporte');
        DB::statement('DROP INDEX IF EXISTS ux_asiento_reversa_de');
        DB::statement('ALTER TABLE asiento_contable DROP COLUMN IF EXISTS plantilla_id');
        DB::statement('ALTER TABLE asiento_contable DROP COLUMN IF EXISTS reversion_automatica');
        DB::statement('ALTER TABLE asiento_contable DROP COLUMN IF EXISTS revertido_por_id');
        DB::statement('ALTER TABLE asiento_contable DROP COLUMN IF EXISTS reversa_de_id');
        DB::statement('ALTER TABLE asiento_contable DROP COLUMN IF EXISTS clasificacion');
    }
};
