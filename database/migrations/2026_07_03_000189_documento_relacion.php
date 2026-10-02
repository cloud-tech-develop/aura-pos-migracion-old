<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V189 — Cadena documental: relaciones entre documentos (fase D0).
 *
 * Traducción de la migración Flyway V189__documento_relacion.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 *
 * Cada fila dice que una línea/documento ORIGEN se aplicó a un documento
 * DESTINO por una cantidad y/o valor. El pendiente de una línea origen es su
 * cantidad original menos la suma de lo aplicado en estado VIGENTE.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS documento_relacion (
    id               BIGSERIAL     PRIMARY KEY,
    empresa_id       INT           NOT NULL,
    origen_tipo      VARCHAR(30)   NOT NULL,
    origen_id        BIGINT        NOT NULL,
    origen_linea_id  BIGINT,
    destino_tipo     VARCHAR(30)   NOT NULL,
    destino_id       BIGINT        NOT NULL,
    destino_linea_id BIGINT,
    cantidad         NUMERIC(18,6),
    valor            NUMERIC(18,2),
    estado           VARCHAR(10)   NOT NULL DEFAULT 'VIGENTE',
    created_by       INT,
    created_at       TIMESTAMP     NOT NULL DEFAULT now()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_documento_relacion_origen
    ON documento_relacion (empresa_id, origen_tipo, origen_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_documento_relacion_origen_linea
    ON documento_relacion (origen_tipo, origen_linea_id)
    WHERE origen_linea_id IS NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_documento_relacion_destino
    ON documento_relacion (empresa_id, destino_tipo, destino_id)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS documento_relacion');
    }
};
