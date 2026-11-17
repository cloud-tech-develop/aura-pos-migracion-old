<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V249 — Módulo Servicio técnico: órdenes, equipo, fotos, items e historial.
 *
 * Traducción de la migración Flyway V249__servicio_tecnico.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE IF NOT EXISTS servicio_tecnico_config (
    empresa_id        INT          PRIMARY KEY REFERENCES empresa(id),
    prefijo           VARCHAR(10)  NOT NULL DEFAULT \'OS\',
    siguiente_numero  INT          NOT NULL DEFAULT 1 CHECK (siguiente_numero > 0),
    garantia_dias     INT          NOT NULL DEFAULT 30 CHECK (garantia_dias >= 0),
    condiciones       TEXT,
    created_at        TIMESTAMP    NOT NULL DEFAULT now(),
    updated_at        TIMESTAMP
)');
        DB::statement('CREATE TABLE IF NOT EXISTS servicio_orden (
    id                  BIGSERIAL     PRIMARY KEY,
    empresa_id          INT           NOT NULL REFERENCES empresa(id),
    sucursal_id         BIGINT,
    numero              INT           NOT NULL,
    codigo              VARCHAR(30)   NOT NULL,
    cliente_id          BIGINT        NOT NULL REFERENCES tercero(id),
    contacto_telefono   VARCHAR(30),
    contacto_email      VARCHAR(150),
    tecnico_id          BIGINT,
    estado              VARCHAR(25)   NOT NULL DEFAULT \'RECIBIDO\',
    prioridad           VARCHAR(10)   NOT NULL DEFAULT \'NORMAL\',
    fecha_recibido      TIMESTAMP     NOT NULL DEFAULT now(),
    fecha_estimada      DATE,
    fecha_entregado     TIMESTAMP,
    diagnostico         TEXT,
    trabajo_realizado   TEXT,
    observaciones       TEXT,
    garantia_dias       INT           NOT NULL DEFAULT 0 CHECK (garantia_dias >= 0),
    total_repuestos     NUMERIC(18,2) NOT NULL DEFAULT 0,
    total_mano_obra     NUMERIC(18,2) NOT NULL DEFAULT 0,
    total               NUMERIC(18,2) NOT NULL DEFAULT 0,
    total_abonado       NUMERIC(18,2) NOT NULL DEFAULT 0,
    venta_id            BIGINT,
    motivo_cancelacion  VARCHAR(300),
    created_by          BIGINT,
    created_at          TIMESTAMP     NOT NULL DEFAULT now(),
    updated_at          TIMESTAMP,
    CONSTRAINT uq_servicio_orden_id_empresa UNIQUE (id, empresa_id),
    CONSTRAINT uq_servicio_orden_numero UNIQUE (empresa_id, numero),
    CONSTRAINT ck_servicio_orden_estado CHECK (estado IN (\'RECIBIDO\', \'EN_DIAGNOSTICO\',
        \'ESPERANDO_APROBACION\', \'ESPERANDO_REPUESTO\', \'EN_REPARACION\', \'LISTO\', \'ENTREGADO\',
        \'NO_REPARADO\', \'CANCELADO\')),
    CONSTRAINT ck_servicio_orden_prioridad CHECK (prioridad IN (\'NORMAL\', \'URGENTE\'))
)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_servicio_orden_estado ON servicio_orden (empresa_id, estado)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_servicio_orden_cliente ON servicio_orden (empresa_id, cliente_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_servicio_orden_tecnico ON servicio_orden (empresa_id, tecnico_id)');
        DB::statement('CREATE TABLE IF NOT EXISTS servicio_orden_equipo (
    id                    BIGSERIAL    PRIMARY KEY,
    empresa_id            INT          NOT NULL,
    orden_id              BIGINT       NOT NULL,
    tipo                  VARCHAR(20)  NOT NULL DEFAULT \'CELULAR\',
    marca                 VARCHAR(60)  NOT NULL,
    modelo                VARCHAR(80)  NOT NULL,
    imei                  VARCHAR(20),
    imei2                 VARCHAR(20),
    serial                VARCHAR(60),
    color                 VARCHAR(40),
    accesorios            VARCHAR(300),
    estado_fisico         VARCHAR(300),
    enciende              BOOLEAN,
    problemas_reportados  TEXT         NOT NULL,
    created_at            TIMESTAMP    NOT NULL DEFAULT now(),
    updated_at            TIMESTAMP,
    CONSTRAINT uq_servicio_equipo_orden UNIQUE (orden_id),
    CONSTRAINT fk_servicio_equipo_orden FOREIGN KEY (orden_id, empresa_id)
        REFERENCES servicio_orden (id, empresa_id) ON DELETE CASCADE
)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_servicio_equipo_imei ON servicio_orden_equipo (empresa_id, imei)');
        DB::statement('CREATE TABLE IF NOT EXISTS servicio_orden_foto (
    id              BIGSERIAL    PRIMARY KEY,
    empresa_id      INT          NOT NULL,
    orden_id        BIGINT       NOT NULL,
    nombre          VARCHAR(150) NOT NULL,
    mime            VARCHAR(50)  NOT NULL,
    tamano_bytes    INT          NOT NULL CHECK (tamano_bytes > 0 AND tamano_bytes <= 3145728),
    object_key      VARCHAR(120) NOT NULL,
    iv              BYTEA        NOT NULL,
    version_llave   SMALLINT     NOT NULL,
    momento         VARCHAR(15)  NOT NULL DEFAULT \'RECIBIDO\'
                    CHECK (momento IN (\'RECIBIDO\', \'REPARACION\', \'ENTREGA\')),
    created_by      BIGINT,
    created_at      TIMESTAMP    NOT NULL DEFAULT now(),
    CONSTRAINT fk_servicio_foto_orden FOREIGN KEY (orden_id, empresa_id)
        REFERENCES servicio_orden (id, empresa_id) ON DELETE CASCADE
)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_servicio_foto_orden ON servicio_orden_foto (orden_id)');
        DB::statement('CREATE TABLE IF NOT EXISTS servicio_orden_item (
    id              BIGSERIAL     PRIMARY KEY,
    empresa_id      INT           NOT NULL,
    orden_id        BIGINT        NOT NULL,
    tipo            VARCHAR(15)   NOT NULL CHECK (tipo IN (\'REPUESTO\', \'MANO_OBRA\', \'OTRO\')),
    producto_id     BIGINT        REFERENCES producto(id),
    descripcion     VARCHAR(200)  NOT NULL,
    cantidad        NUMERIC(14,4) NOT NULL CHECK (cantidad > 0),
    valor_unitario  NUMERIC(18,2) NOT NULL CHECK (valor_unitario >= 0),
    total           NUMERIC(18,2) NOT NULL,
    created_at      TIMESTAMP     NOT NULL DEFAULT now(),
    CONSTRAINT fk_servicio_item_orden FOREIGN KEY (orden_id, empresa_id)
        REFERENCES servicio_orden (id, empresa_id) ON DELETE CASCADE
)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_servicio_item_orden ON servicio_orden_item (orden_id)');
        DB::statement('CREATE TABLE IF NOT EXISTS servicio_orden_historial (
    id                BIGSERIAL    PRIMARY KEY,
    empresa_id        INT          NOT NULL,
    orden_id          BIGINT       NOT NULL,
    tipo              VARCHAR(10)  NOT NULL CHECK (tipo IN (\'ESTADO\', \'NOTA\')),
    estado_anterior   VARCHAR(25),
    estado_nuevo      VARCHAR(25),
    nota              VARCHAR(1000),
    usuario_id        BIGINT,
    created_at        TIMESTAMP    NOT NULL DEFAULT now(),
    CONSTRAINT fk_servicio_historial_orden FOREIGN KEY (orden_id, empresa_id)
        REFERENCES servicio_orden (id, empresa_id) ON DELETE CASCADE
)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_servicio_historial_orden ON servicio_orden_historial (orden_id, created_at)');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS servicio_orden_historial');
        DB::statement('DROP TABLE IF EXISTS servicio_orden_item');
        DB::statement('DROP TABLE IF EXISTS servicio_orden_foto');
        DB::statement('DROP TABLE IF EXISTS servicio_orden_equipo');
        DB::statement('DROP TABLE IF EXISTS servicio_orden');
        DB::statement('DROP TABLE IF EXISTS servicio_tecnico_config');
    }
};
