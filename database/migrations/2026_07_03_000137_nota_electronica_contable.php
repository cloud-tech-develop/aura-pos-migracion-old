<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V137 — nota electrónica: base gravable e IVA para contabilizarla.
 *
 * Traducción fiel de la migración Flyway V137__nota_electronica_contable.sql
 * (aura-back-old): cada sentencia de PostgreSQL va en su propio DB::statement.
 * Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- F5: base gravable e IVA de la nota, para contabilizarla (reversa de ingreso).
-- Se calculan al persistir a partir de los items enviados a Factus.
ALTER TABLE nota_electronica ADD COLUMN IF NOT EXISTS base_gravable NUMERIC(15,2)
MIG_SQL);

        DB::statement('ALTER TABLE nota_electronica ADD COLUMN IF NOT EXISTS iva NUMERIC(15,2)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE nota_electronica DROP COLUMN IF EXISTS iva');
        DB::statement('ALTER TABLE nota_electronica DROP COLUMN IF EXISTS base_gravable');
    }
};
