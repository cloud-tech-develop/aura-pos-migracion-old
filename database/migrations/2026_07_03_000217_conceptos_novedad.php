<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V217 — Conceptos de novedad (rediseño de nómina R3.2d).
 *
 * Traducción de la migración Flyway V217__conceptos_novedad.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina DROP CONSTRAINT IF EXISTS chk_cn_base
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD CONSTRAINT chk_cn_base CHECK (base IN ('SALARIO', 'SALARIO_MAS_AUXILIO', 'IBC',
    'DEVENGADO_TOTAL', 'FIJO', 'MANUAL', 'NOVEDAD'))
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO concepto_nomina (empresa_id, codigo, nombre, clase, constituye_ibc, base, vigente_desde, codigo_dian,
       orden, activo, created_at)
SELECT NULL, v.codigo, v.nombre, 'DEVENGADO', v.ibc, 'NOVEDAD', DATE '2020-01-01', v.dian, v.orden, TRUE, now()
  FROM (VALUES
       ('HORA_EXTRA_DIURNA',    'Hora extra diurna',             TRUE,  'HED',            30),
       ('HORA_EXTRA_NOCTURNA',  'Hora extra nocturna',           TRUE,  'HEN',            31),
       ('HORA_EXTRA_DOMINICAL', 'Hora extra dominical',          TRUE,  'HEDDF',          32),
       ('HORA_EXTRA_FESTIVO',   'Hora extra festiva',            TRUE,  'HEDDF',          33),
       ('INCAPACIDAD',          'Incapacidad',                   TRUE,  'Incapacidades',  40),
       ('LICENCIA_REMUNERADA',  'Licencia remunerada',           TRUE,  'LicenciaR',      41),
       ('LICENCIA_MATERNIDAD',  'Licencia de maternidad/paternidad', TRUE, 'LicenciaMP',  42),
       ('BONO',                 'Bonificación',                  FALSE, 'Bonificaciones', 50),
       ('COMISION',             'Comisión',                      TRUE,  'Comisiones',     51),
       ('OTRO_DEVENGO',         'Otro devengo',                  TRUE,  'OtrosConceptos', 52)
       ) AS v(codigo, nombre, ibc, dian, orden)
 WHERE NOT EXISTS (SELECT 1 FROM concepto_nomina c WHERE c.empresa_id IS NULL AND c.codigo = v.codigo)
MIG_SQL);
    }

    public function down(): void
    {
        // Sin reversa: las nóminas liquidadas quedan con líneas de estos conceptos.
    }
};
