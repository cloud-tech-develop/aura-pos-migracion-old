<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V224 — Nómina electrónica mensual: documento por contrato y mes (contrato_id).
 *
 * Traducción de la migración Flyway V224__nomina_electronica_mensual.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE nomina_electronica ADD COLUMN IF NOT EXISTS contrato_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE nomina_electronica ne
   SET contrato_id = n.contrato_id
  FROM nomina n
 WHERE n.id = ne.nomina_id
   AND ne.contrato_id IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS ix_ne_contrato_mes
    ON nomina_electronica (empresa_id, contrato_id, agno, mes)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ix_ne_contrato_mes');
        DB::statement('ALTER TABLE nomina_electronica DROP COLUMN IF EXISTS contrato_id');
    }
};
