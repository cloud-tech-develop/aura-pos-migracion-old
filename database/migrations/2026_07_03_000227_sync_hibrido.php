<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V227 — Modo híbrido (docs/MODO_HIBRIDO.md): servidor local por tienda que
 * trabaja sin internet y se sincroniza con la nube.
 *
 * Las cinco tablas existen en las dos bases (mismo esquema), pero cada lado usa
 * las suyas:
 *   nube  : sync_nodo, sync_recibido, sync_id_map
 *   local : sync_outbox, sync_estado
 *
 * Traducción de la migración Flyway V227__sync_hibrido.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Servidor local registrado en la nube. El token se guarda como SHA-256:
        // el valor en claro se muestra una sola vez al crearlo. prefijo_ventas es
        // la serie propia del nodo, para que sus consecutivos nunca choquen con
        // los que la nube saca para la misma sucursal.
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS sync_nodo (
    id               BIGSERIAL PRIMARY KEY,
    empresa_id       INTEGER      NOT NULL REFERENCES empresa (id),
    sucursal_id      INTEGER      REFERENCES sucursal (id),
    codigo           VARCHAR(40)  NOT NULL,
    nombre           VARCHAR(120) NOT NULL,
    prefijo_ventas   VARCHAR(20),
    token_hash       VARCHAR(64)  NOT NULL,
    activo           BOOLEAN      NOT NULL DEFAULT TRUE,
    ultimo_contacto  TIMESTAMP,
    created_at       TIMESTAMP    NOT NULL DEFAULT now(),
    CONSTRAINT uq_sync_nodo_codigo UNIQUE (empresa_id, codigo),
    CONSTRAINT uq_sync_nodo_token UNIQUE (token_hash)
)
MIG_SQL);

        // Comandos que la nube ya recibió de un nodo. El uuid hace el envío
        // idempotente: si el nodo reintenta un lote ya procesado, no se duplica.
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS sync_recibido (
    id            BIGSERIAL PRIMARY KEY,
    nodo_id       BIGINT       NOT NULL REFERENCES sync_nodo (id),
    uuid          VARCHAR(36)  NOT NULL,
    tipo          VARCHAR(40)  NOT NULL,
    id_local      BIGINT,
    estado        VARCHAR(10)  NOT NULL,
    respuesta     TEXT,
    error         TEXT,
    created_at    TIMESTAMP    NOT NULL DEFAULT now(),
    CONSTRAINT uq_sync_recibido_uuid UNIQUE (uuid)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_sync_recibido_nodo ON sync_recibido (nodo_id, created_at)
MIG_SQL);

        // Equivalencia entre el id que el registro tiene en el nodo y el que
        // recibió en la nube al reproducirse (venta, turno, tercero...).
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS sync_id_map (
    nodo_id     BIGINT      NOT NULL REFERENCES sync_nodo (id),
    tabla       VARCHAR(60) NOT NULL,
    id_local    BIGINT      NOT NULL,
    id_nube     BIGINT      NOT NULL,
    created_at  TIMESTAMP   NOT NULL DEFAULT now(),
    PRIMARY KEY (nodo_id, tabla, id_local)
)
MIG_SQL);

        // Cola de salida del nodo: cada operación que debe llegar a la nube, en
        // orden. estado: PENDIENTE · ENVIADO · ERROR · DESCARTADO.
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS sync_outbox (
    id            BIGSERIAL PRIMARY KEY,
    uuid          VARCHAR(36)  NOT NULL,
    tipo          VARCHAR(40)  NOT NULL,
    id_local      BIGINT,
    empresa_id    INTEGER      NOT NULL,
    usuario_id    BIGINT,
    fecha         TIMESTAMP    NOT NULL,
    payload       TEXT         NOT NULL,
    estado        VARCHAR(12)  NOT NULL DEFAULT 'PENDIENTE',
    intentos      INTEGER      NOT NULL DEFAULT 0,
    ultimo_error  TEXT,
    respuesta     TEXT,
    created_at    TIMESTAMP    NOT NULL DEFAULT now(),
    enviado_at    TIMESTAMP,
    CONSTRAINT uq_sync_outbox_uuid UNIQUE (uuid)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_sync_outbox_pendiente ON sync_outbox (id) WHERE estado IN ('PENDIENTE', 'ERROR')
MIG_SQL);

        // Estado del nodo: identidad recibida de la nube, última sincronización
        // y pisos de consecutivos.
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS sync_estado (
    clave       VARCHAR(120) PRIMARY KEY,
    valor       TEXT,
    updated_at  TIMESTAMP NOT NULL DEFAULT now()
)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS sync_estado');
        DB::statement('DROP TABLE IF EXISTS sync_outbox');
        DB::statement('DROP TABLE IF EXISTS sync_id_map');
        DB::statement('DROP TABLE IF EXISTS sync_recibido');
        DB::statement('DROP TABLE IF EXISTS sync_nodo');
    }
};
