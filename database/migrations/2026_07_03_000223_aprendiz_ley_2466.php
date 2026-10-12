<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V223 — Aprendiz SENA, Ley 2466 de 2025: apoyo 75 % lectiva / 100 % práctica.
 *
 * Traducción de la migración Flyway V223__aprendiz_ley_2466.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE nomina_config ALTER COLUMN aprendiz_pct_lectiva SET DEFAULT 75
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE nomina_config ALTER COLUMN aprendiz_pct_practica SET DEFAULT 100
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE nomina_config SET aprendiz_pct_lectiva = 75 WHERE aprendiz_pct_lectiva = 50
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE nomina_config SET aprendiz_pct_practica = 100 WHERE aprendiz_pct_practica = 75
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE nomina_config ALTER COLUMN aprendiz_pct_lectiva SET DEFAULT 50');
        DB::statement('ALTER TABLE nomina_config ALTER COLUMN aprendiz_pct_practica SET DEFAULT 75');
    }
};
