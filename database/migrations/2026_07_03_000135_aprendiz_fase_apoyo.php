<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V135 — aprendiz SENA: fase y apoyo de sostenimiento (B-10).
 *
 * Traducción de la migración Flyway V135__aprendiz_fase_apoyo.sql (aura-back-old):
 * cada sentencia de PostgreSQL va en su propio DB::statement. La original usaba
 * ADD COLUMN / ADD CONSTRAINT a secas; aquí van con IF NOT EXISTS y DROP previo
 * del constraint para que la migración sea repetible.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- El aprendiz no gana salario sino apoyo de sostenimiento, y su seguridad social
-- depende de la FASE:
--   · LECTIVA  → apoyo 50% SMMLV, solo salud (EPS).
--   · PRÁCTICA → apoyo 75% SMMLV, salud + ARL.
-- (Sin pensión, parafiscales ni prestaciones en ninguna fase.)
--
-- Se agrega `fase` al contrato y los dos porcentajes al config (parametrizables /
-- versionables, como el SMMLV: si sube el desempleo la práctica puede ir a 100%).
--
-- Seguro: `fase` es nullable (los contratos no-aprendiz quedan en NULL); los
-- porcentajes traen DEFAULT, así que las filas de config existentes no se rompen.

ALTER TABLE contrato_laboral
    ADD COLUMN IF NOT EXISTS fase VARCHAR(20)
MIG_SQL);

        DB::statement('ALTER TABLE contrato_laboral DROP CONSTRAINT IF EXISTS chk_contrato_fase');

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral
    ADD CONSTRAINT chk_contrato_fase CHECK (fase IS NULL OR fase IN ('LECTIVA', 'PRACTICA'))
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE nomina_config
    ADD COLUMN IF NOT EXISTS aprendiz_pct_lectiva NUMERIC(5,2) NOT NULL DEFAULT 50
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE nomina_config
    ADD COLUMN IF NOT EXISTS aprendiz_pct_practica NUMERIC(5,2) NOT NULL DEFAULT 75
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE nomina_config   DROP COLUMN IF EXISTS aprendiz_pct_practica');
        DB::statement('ALTER TABLE nomina_config   DROP COLUMN IF EXISTS aprendiz_pct_lectiva');
        DB::statement('ALTER TABLE contrato_laboral DROP CONSTRAINT IF EXISTS chk_contrato_fase');
        DB::statement('ALTER TABLE contrato_laboral DROP COLUMN IF EXISTS fase');
    }
};
