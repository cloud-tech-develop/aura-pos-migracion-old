<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V109 — nomina electronica.
 *
 * Traducción fiel de la migración Flyway V109__nomina_electronica.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V109: Fase 5 — nómina electrónica (DIAN vía Factus) ─────────────────────
--
-- Obligatoria ante la DIAN. Sin esto el módulo no es vendible como nómina formal.
--
-- Factus confirmado: emite nómina electrónica. Verificar el contrato específico
-- del endpoint al implementar (la integración actual, FactusService.generarFactura,
-- es solo factura de venta — nómina es otro documento, con CUNE en vez de CUFE).
--
-- Seguro sin tocar código: solo crea tablas nuevas.

CREATE TABLE IF NOT EXISTS nomina_electronica (
    id                 BIGSERIAL    PRIMARY KEY,
    empresa_id         INT          NOT NULL REFERENCES empresa(id),
    nomina_id          BIGINT       NOT NULL REFERENCES nomina(id),

    agno               INT          NOT NULL,
    mes                INT          NOT NULL,

    -- Consecutivo propio del documento de nómina electrónica, independiente
    -- del de facturación. Se RESERVA antes de enviar (ver nota de idempotencia).
    consecutivo        BIGINT       NOT NULL,
    prefijo            VARCHAR(10),

    -- Notas de ajuste
    es_ajuste          BOOLEAN      NOT NULL DEFAULT FALSE,
    nomina_ajustada_id BIGINT       REFERENCES nomina_electronica(id),

    estado             VARCHAR(20)  NOT NULL DEFAULT 'PENDIENTE',
    intentos           INT          NOT NULL DEFAULT 0,

    -- Identificador único que devuelve la DIAN
    cune               VARCHAR(120),
    fecha_envio        TIMESTAMP,
    fecha_respuesta    TIMESTAMP,

    -- Snapshot de lo que se envió. NO se regenera: es el documento tal cual
    -- quedó ante la DIAN. Mismo criterio que pila_cotizante.cod_eps.
    payload_json       JSONB,
    xml                TEXT,

    created_at         TIMESTAMP    NOT NULL DEFAULT NOW(),
    updated_at         TIMESTAMP    NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_ne_estado CHECK (estado IN
        ('PENDIENTE', 'ENVIADO', 'ACEPTADO', 'RECHAZADO', 'ANULADO')),
    CONSTRAINT chk_ne_mes CHECK (mes BETWEEN 1 AND 12),

    -- ⚠️ EL GUARDARRAÍL MÁS IMPORTANTE DE ESTA FASE.
    -- Impide que un reintento genere un documento nuevo ante la DIAN.
    CONSTRAINT uq_ne_consecutivo UNIQUE (empresa_id, agno, consecutivo),

    -- Una nómina no puede tener dos documentos originales (sí ajustes).
    CONSTRAINT chk_ne_ajuste CHECK (es_ajuste = TRUE OR nomina_ajustada_id IS NULL)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_ne_nomina  ON nomina_electronica(nomina_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_ne_estado  ON nomina_electronica(estado) WHERE estado IN ('PENDIENTE','RECHAZADO')
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_ne_periodo ON nomina_electronica(empresa_id, agno, mes)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Un solo documento ORIGINAL por nómina. Los ajustes son filas aparte.
CREATE UNIQUE INDEX IF NOT EXISTS ux_ne_original
    ON nomina_electronica(nomina_id) WHERE es_ajuste = FALSE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Log de cada intento contra la DIAN ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS nomina_electronica_log (
    id                    BIGSERIAL   PRIMARY KEY,
    nomina_electronica_id BIGINT      NOT NULL REFERENCES nomina_electronica(id) ON DELETE CASCADE,
    intento               INT         NOT NULL DEFAULT 1,
    codigo_respuesta      VARCHAR(20),
    mensaje_respuesta     TEXT,
    request_body          TEXT,
    response_body         TEXT,
    duracion_ms           INT,
    created_at            TIMESTAMP   NOT NULL DEFAULT NOW()
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_ne_log ON nomina_electronica_log(nomina_electronica_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Consecutivo por empresa/año ─────────────────────────────────────────────
-- Secuencia propia, reservada ANTES de enviar. No usar el consecutivo de
-- facturación: son numeraciones distintas ante la DIAN.
CREATE TABLE IF NOT EXISTS nomina_electronica_consecutivo (
    empresa_id INT    NOT NULL,
    agno       INT    NOT NULL,
    ultimo     BIGINT NOT NULL DEFAULT 0,
    PRIMARY KEY (empresa_id, agno)
)
MIG_SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_electronica_consecutivo');
        Schema::dropIfExists('nomina_electronica_log');
        Schema::dropIfExists('nomina_electronica');
    }
};
