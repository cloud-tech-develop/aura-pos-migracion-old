<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V117 — proceso nomina.
 *
 * Traducción fiel de la migración Flyway V117__proceso_nomina.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V117: Fase 7 — procesos asíncronos con progreso ─────────────────────────
--
-- PROBLEMA: `liquidarPeriodoCompleto()` corre síncrono dentro del request HTTP.
-- Con 30 empleados va bien; con 500 y provisiones, timeout. Lo mismo aplicará
-- a los envíos DIAN (Fase 5) y a la generación de PILA (Fase 6).
--
-- Equivale a `nom_procesos_logs` + ComProgressEvent del ERP de referencia.
--
-- TRANSVERSAL: se puede adelantar en cualquier momento si el volumen aprieta.
--
-- Seguro sin tocar código: solo crea tablas nuevas.

CREATE TABLE IF NOT EXISTS proceso_nomina (
    id           BIGSERIAL     PRIMARY KEY,
    empresa_id   INT           NOT NULL REFERENCES empresa(id),

    tipo         VARCHAR(40)   NOT NULL,
    referencia_id BIGINT,                     -- periodo_id, nomina_id, etc.

    estado       VARCHAR(20)   NOT NULL DEFAULT 'PENDIENTE',
    progreso     INT           NOT NULL DEFAULT 0,   -- 0..100
    mensaje      VARCHAR(300),

    total_items     INT        NOT NULL DEFAULT 0,
    items_ok        INT        NOT NULL DEFAULT 0,
    items_error     INT        NOT NULL DEFAULT 0,

    -- Errores por item, sin abortar el lote.
    errores      JSONB,

    -- Quién y cuándo. El ERP registra procesa/reversa por separado; aquí igual.
    usuario_id   BIGINT,
    iniciado_at  TIMESTAMP     NOT NULL DEFAULT NOW(),
    finalizado_at TIMESTAMP,

    -- Reversa
    reversado_por BIGINT,
    reversado_at  TIMESTAMP,

    CONSTRAINT chk_proc_estado CHECK (estado IN
        ('PENDIENTE', 'EN_PROCESO', 'COMPLETADO', 'COMPLETADO_CON_ERRORES',
         'FALLIDO', 'REVERSADO')),
    CONSTRAINT chk_proc_progreso CHECK (progreso BETWEEN 0 AND 100),
    CONSTRAINT chk_proc_tipo CHECK (tipo IN (
        'LIQUIDACION_PERIODO',
        'LIQUIDACION_PRESTACIONES',
        'NOMINA_ELECTRONICA',
        'NOMINA_ELECTRONICA_AJUSTE',
        'PILA_GENERACION',
        'CONTABILIZACION',
        'IMPORTACION'
    ))
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_proceso_empresa ON proceso_nomina(empresa_id, tipo)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_proceso_activo
    ON proceso_nomina(estado) WHERE estado IN ('PENDIENTE', 'EN_PROCESO')
MIG_SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('proceso_nomina');
    }
};
