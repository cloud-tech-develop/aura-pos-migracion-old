<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V238 — proceso_nomina.estado a 30 (COMPLETADO_CON_ERRORES no cabía en 20) y
 * conceptos de ley RECARGO_NOCTURNO, RECARGO_DOMINICAL_FESTIVO y
 * HORA_EXTRA_DOMINICAL_FESTIVA que generan asistencia y frentes.
 *
 * Traducción de la migración Flyway V238__nomina_proceso_estado_conceptos_recargo.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE proceso_nomina ALTER COLUMN estado TYPE VARCHAR(30)');

        DB::statement(<<<'MIG_SQL'
INSERT INTO concepto_nomina (empresa_id, codigo, nombre, clase, constituye_ibc, base, vigente_desde, codigo_dian,
       orden, activo, created_at, numero, tipo_id, de_ley, hace_base_prestacional)
SELECT NULL, v.codigo, v.nombre, 'DEVENGADO',
       COALESCE((SELECT h.constituye_ibc FROM concepto_nomina h
                  WHERE h.empresa_id IS NULL AND h.codigo = 'HORA_EXTRA_DIURNA'
                  ORDER BY h.vigente_desde DESC LIMIT 1), TRUE),
       'NOVEDAD', DATE '2020-01-01', v.dian, v.orden, TRUE, now(), v.numero, v.tipo_id, TRUE,
       COALESCE((SELECT h.hace_base_prestacional FROM concepto_nomina h
                  WHERE h.empresa_id IS NULL AND h.codigo = 'HORA_EXTRA_DIURNA'
                  ORDER BY h.vigente_desde DESC LIMIT 1), FALSE)
  FROM (VALUES
       ('RECARGO_NOCTURNO',             'Recargo nocturno',                  'HRN',   34, 14, 7),
       ('RECARGO_DOMINICAL_FESTIVO',    'Recargo dominical/festivo',         'HRDDF', 35, 15, 8),
       ('HORA_EXTRA_DOMINICAL_FESTIVA', 'Hora extra dominical/festiva',      'HEDDF', 36, 16, 5)
       ) AS v(codigo, nombre, dian, orden, numero, tipo_id)
 WHERE NOT EXISTS (SELECT 1 FROM concepto_nomina c WHERE c.empresa_id IS NULL AND c.codigo = v.codigo)
   AND NOT EXISTS (SELECT 1 FROM concepto_nomina c WHERE c.empresa_id IS NULL AND c.numero = v.numero)
MIG_SQL);
    }

    public function down(): void
    {
        // Sin reversa: ampliar la columna y crear conceptos de ley no se deshace.
    }
};
