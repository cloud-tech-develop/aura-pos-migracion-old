<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V114 — certificados.
 *
 * Traducción fiel de la migración Flyway V114__certificados.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V114: Fase 10 — certificados y desprendible ─────────────────────────────
--
-- Depende enteramente de la Fase 3.b (nomina_detalle con `traza`): sin el
-- desglose no hay nada que imprimir.
--
-- Seguro sin tocar código: solo crea tablas nuevas.

CREATE TABLE IF NOT EXISTS certificado_emitido (
    id           BIGSERIAL     PRIMARY KEY,
    empresa_id   INT           NOT NULL REFERENCES empresa(id),
    contrato_id  BIGINT        REFERENCES contrato_laboral(id),
    tercero_id   BIGINT        NOT NULL REFERENCES tercero(id),

    tipo         VARCHAR(30)   NOT NULL,
    agno         INT,                              -- para certificados anuales
    nomina_id    BIGINT        REFERENCES nomina(id),  -- para desprendibles

    -- SNAPSHOT del contenido tal como se emitió.
    -- Un certificado es un documento con valor probatorio: NO se regenera.
    -- Si el empleado vuelve por el mismo certificado dentro de un año, debe
    -- recibir EXACTAMENTE el mismo documento — aunque los datos hayan cambiado.
    -- Mismo criterio que pila_cotizante.cod_eps y nomina_electronica.xml:
    -- los documentos emitidos guardan literales; los maestros guardan FK.
    contenido_json JSONB,
    pdf_ruta     VARCHAR(500),

    emitido_por  BIGINT,
    emitido_at   TIMESTAMP     NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_cert_tipo CHECK (tipo IN (
        'DESPRENDIBLE',              -- detalle de una nómina
        'INGRESOS_RETENCIONES',      -- formato 220 DIAN, anual
        'LABORAL',                   -- cargo, salario, fechas
        'LABORAL_CON_SALARIO',
        'CESANTIAS'                  -- para retiro parcial
    ))
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_cert_tercero  ON certificado_emitido(tercero_id, tipo)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_cert_contrato ON certificado_emitido(contrato_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_cert_agno     ON certificado_emitido(empresa_id, tipo, agno)
MIG_SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('certificado_emitido');
    }
};
