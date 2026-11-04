<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V233 — Fórmulas, hace base prestacional y centro de costo por concepto de nómina
 * (docs/PLAN_CONCEPTOS_CARGOS_NOMINA.md, F2/F3/F7).
 * 
 * Traducción de la migración Flyway V233__concepto_formula_bases_centro.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD COLUMN IF NOT EXISTS formula TEXT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD COLUMN IF NOT EXISTS hace_base_prestacional BOOLEAN NOT NULL DEFAULT FALSE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD COLUMN IF NOT EXISTS centro_costo_id BIGINT REFERENCES centros_costos(id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina DROP CONSTRAINT IF EXISTS chk_cn_base
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD CONSTRAINT chk_cn_base CHECK (base IN ('SALARIO', 'SALARIO_MAS_AUXILIO', 'IBC',
    'DEVENGADO_TOTAL', 'FIJO', 'MANUAL', 'NOVEDAD', 'FORMULA'))
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina DROP CONSTRAINT IF EXISTS chk_cn_formula
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD CONSTRAINT chk_cn_formula CHECK (base <> 'FORMULA' OR formula IS NOT NULL)
MIG_SQL);
    }

    public function down(): void
    {
        // Sin reversa: migración aditiva sobre datos en uso.
    }
};
