<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V160 — registro de "Pasar a unidad" (cambio de unidad base de un producto).
 *
 * Traducción de la migración Flyway V160__producto_cambio_unidad.sql (aura-back-old).
 *
 * Guarda el factor aplicado y el último id de compra, merma, obsequio y traslado
 * al momento del cambio: esos documentos tienen cantidades en la unidad vieja y
 * el servicio no deja anularlos ni editarlos para ese producto. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS producto_cambio_unidad (
    id                       BIGSERIAL     PRIMARY KEY,
    empresa_id               INTEGER       NOT NULL,
    producto_id              BIGINT        NOT NULL REFERENCES producto (id),
    factor                   NUMERIC(18,6) NOT NULL,
    unidad_anterior_id       BIGINT,
    unidad_nueva_id          BIGINT,
    presentacion_grande_id   BIGINT        REFERENCES producto_presentacion (id),
    presentacion_pequena_id  BIGINT        REFERENCES producto_presentacion (id),
    ultimo_compra_id         BIGINT        NOT NULL DEFAULT 0,
    ultimo_merma_id          BIGINT        NOT NULL DEFAULT 0,
    ultimo_obsequio_id       BIGINT        NOT NULL DEFAULT 0,
    ultimo_traslado_id       BIGINT        NOT NULL DEFAULT 0,
    usuario_id               BIGINT,
    created_at               TIMESTAMP     NOT NULL DEFAULT now()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON TABLE producto_cambio_unidad IS
    'Cada "Pasar a unidad": factor N aplicado y últimos ids de documentos sin presentación; los anteriores no se anulan ni editan para ese producto.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_producto_cambio_unidad_producto
    ON producto_cambio_unidad (producto_id)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS producto_cambio_unidad');
    }
};
