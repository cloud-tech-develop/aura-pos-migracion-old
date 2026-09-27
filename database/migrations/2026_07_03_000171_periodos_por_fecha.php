<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V171 — el período contable lo define la FECHA del asiento.
 *
 * Antes existía un solo período ABIERTO por empresa y todo asiento caía en él
 * sin mirar su fecha: cerrar enero dejaba el sistema sin período y una factura
 * de enero registrada en febrero quedaba marcada en febrero. Desde aquí cada mes
 * es su propio período, pueden convivir varios abiertos, el mes se abre solo al
 * primer asiento y solo se bloquea si ESE mes está cerrado. Agrega la reapertura
 * con motivo y traza.
 *
 * Traducción de la migración Flyway V171__periodos_por_fecha.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE periodo_contable ADD COLUMN IF NOT EXISTS reaperturas INTEGER NOT NULL DEFAULT 0
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE periodo_contable ADD COLUMN IF NOT EXISTS fecha_reapertura TIMESTAMP
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE periodo_contable ADD COLUMN IF NOT EXISTS usuario_reapertura_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE periodo_contable ADD COLUMN IF NOT EXISTS motivo_reapertura VARCHAR(300)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE periodo_contable ADD COLUMN IF NOT EXISTS creado_automatico BOOLEAN NOT NULL DEFAULT FALSE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS periodo_contable_evento (
    id                  BIGSERIAL     PRIMARY KEY,
    periodo_contable_id BIGINT        NOT NULL REFERENCES periodo_contable(id),
    empresa_id          INTEGER       NOT NULL,
    tipo                VARCHAR(12)   NOT NULL,
    motivo              VARCHAR(300),
    usuario_id          BIGINT,
    created_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ck_periodo_evento_tipo CHECK (tipo IN ('APERTURA','CIERRE','REAPERTURA'))
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_periodo_evento_periodo
    ON periodo_contable_evento (periodo_contable_id, created_at DESC)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_periodo_contable_empresa_mes
    ON periodo_contable (empresa_id, anio, mes, estado)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO periodo_contable (empresa_id, anio, mes, estado, fecha_apertura, creado_automatico, observaciones)
SELECT DISTINCT a.empresa_id,
       EXTRACT(YEAR  FROM a.fecha)::SMALLINT,
       EXTRACT(MONTH FROM a.fecha)::SMALLINT,
       'ABIERTO',
       date_trunc('month', a.fecha)::date,
       TRUE,
       'Creado por V171 a partir de los asientos de ese mes'
FROM asiento_contable a
WHERE a.fecha IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM periodo_contable p
      WHERE p.empresa_id = a.empresa_id
        AND p.anio = EXTRACT(YEAR  FROM a.fecha)::SMALLINT
        AND p.mes  = EXTRACT(MONTH FROM a.fecha)::SMALLINT)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO periodo_contable_evento (periodo_contable_id, empresa_id, tipo, motivo, usuario_id, created_at)
SELECT p.id, p.empresa_id, 'APERTURA', p.observaciones, p.usuario_apertura_id,
       COALESCE(p.fecha_apertura::timestamp, p.created_at)
FROM periodo_contable p
WHERE NOT EXISTS (SELECT 1 FROM periodo_contable_evento e
                  WHERE e.periodo_contable_id = p.id AND e.tipo = 'APERTURA')
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO periodo_contable_evento (periodo_contable_id, empresa_id, tipo, motivo, usuario_id, created_at)
SELECT p.id, p.empresa_id, 'CIERRE', NULL, p.usuario_cierre_id, p.fecha_cierre::timestamp
FROM periodo_contable p
WHERE p.estado = 'CERRADO' AND p.fecha_cierre IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM periodo_contable_evento e
                  WHERE e.periodo_contable_id = p.id AND e.tipo = 'CIERRE')
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE asiento_contable a
SET periodo_contable_id = p.id
FROM periodo_contable p
WHERE p.empresa_id = a.empresa_id
  AND p.anio = EXTRACT(YEAR  FROM a.fecha)::SMALLINT
  AND p.mes  = EXTRACT(MONTH FROM a.fecha)::SMALLINT
  AND a.fecha IS NOT NULL
  AND a.periodo_contable_id IS DISTINCT FROM p.id
  AND (a.periodo_contable_id IS NULL
       OR EXISTS (SELECT 1 FROM periodo_contable actual
                  WHERE actual.id = a.periodo_contable_id AND actual.estado = 'ABIERTO'))
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS periodo_contable_evento');
        DB::statement('ALTER TABLE periodo_contable DROP COLUMN IF EXISTS creado_automatico');
        DB::statement('ALTER TABLE periodo_contable DROP COLUMN IF EXISTS motivo_reapertura');
        DB::statement('ALTER TABLE periodo_contable DROP COLUMN IF EXISTS usuario_reapertura_id');
        DB::statement('ALTER TABLE periodo_contable DROP COLUMN IF EXISTS fecha_reapertura');
        DB::statement('ALTER TABLE periodo_contable DROP COLUMN IF EXISTS reaperturas');
    }
};
