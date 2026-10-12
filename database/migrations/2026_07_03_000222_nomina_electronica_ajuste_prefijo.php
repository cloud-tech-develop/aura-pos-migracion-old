<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V222 — Nómina electrónica: número único por prefijo (notas de ajuste Factus v2).
 *
 * Traducción de la migración Flyway V222__nomina_electronica_ajuste_prefijo.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE nomina_electronica DROP CONSTRAINT IF EXISTS uq_ne_consecutivo
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_ne_prefijo_consecutivo
    ON nomina_electronica (empresa_id, agno, COALESCE(prefijo, ''), consecutivo)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS uq_ne_prefijo_consecutivo');
    }
};
