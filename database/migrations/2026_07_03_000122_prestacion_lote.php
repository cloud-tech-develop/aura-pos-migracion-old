<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V122 — prestacion lote.
 *
 * Traducción fiel de la migración Flyway V122__prestacion_lote.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V122: agrupar prestaciones en un "lote" ────────────────────────────────
--
-- Una liquidación definitiva genera varias filas (cesantías, intereses, prima,
-- vacaciones, indemnización). El `lote` las agrupa: el listado muestra una sola
-- fila con el total, y el detalle abre el desglose. Una liquidación individual
-- es un lote de una sola fila.

ALTER TABLE liquidacion_prestacion
    ADD COLUMN IF NOT EXISTS lote VARCHAR(48)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Backfill: cada fila existente queda como su propio lote.
UPDATE liquidacion_prestacion SET lote = 'P-' || id WHERE lote IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_liq_prest_lote ON liquidacion_prestacion(lote)
MIG_SQL);
    }

    public function down(): void
    {
        // Migración de datos/columnas (ver up). Reversa no automática.
    }
};
