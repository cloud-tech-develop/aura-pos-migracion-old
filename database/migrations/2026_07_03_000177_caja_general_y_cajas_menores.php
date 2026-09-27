<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V177 — Caja general (110505) y cajas menores (110510) según el PUC; el concepto
 * CAJA y la forma de pago EFECTIVO pasan de la 1105 a la 110505.
 *
 * Traducción de la migración Flyway V177__caja_general_y_cajas_menores.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
UPDATE plan_cuenta p
   SET codigo = '110510',
       nombre = 'Cajas menores'
 WHERE p.codigo = '110505'
   AND p.nombre ILIKE '%menor%'
   AND NOT EXISTS (SELECT 1 FROM plan_cuenta x
                    WHERE x.empresa_id = p.empresa_id AND x.codigo = '110510')
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id,
                         activa, auxiliar, es_medio_pago, created_at)
SELECT p.empresa_id, v.codigo, v.nombre, 'ACTIVO', 'DEBITO', 4, p.id, TRUE, TRUE, TRUE, now()
  FROM (VALUES ('110505', 'Caja general'),
               ('110510', 'Cajas menores')) AS v(codigo, nombre)
  JOIN plan_cuenta p ON p.codigo = '1105'
 WHERE NOT EXISTS (SELECT 1 FROM plan_cuenta x
                    WHERE x.empresa_id = p.empresa_id AND x.codigo = v.codigo)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE cuenta_config cc
   SET cuenta_id = g.id,
       updated_at = now()
  FROM plan_cuenta c, plan_cuenta g
 WHERE cc.concepto = 'CAJA'
   AND c.id = cc.cuenta_id
   AND c.codigo = '1105'
   AND g.empresa_id = cc.empresa_id
   AND g.codigo = '110505'
   AND g.nombre ILIKE '%general%'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE forma_pago_contable f
   SET cuenta_contable_id = g.id
  FROM plan_cuenta c, plan_cuenta g
 WHERE c.id = f.cuenta_contable_id
   AND c.codigo = '1105'
   AND g.empresa_id = f.empresa_id
   AND g.codigo = '110505'
   AND g.nombre ILIKE '%general%'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO cuenta_config (empresa_id, concepto, cuenta_id, created_at)
SELECT c.empresa_id, 'CAJA', c.id, now()
  FROM plan_cuenta c
 WHERE c.codigo = '1105'
   AND NOT EXISTS (SELECT 1 FROM cuenta_config x
                    WHERE x.empresa_id = c.empresa_id AND x.concepto = 'CAJA')
   AND NOT EXISTS (SELECT 1 FROM plan_cuenta g
                    WHERE g.empresa_id = c.empresa_id AND g.codigo = '110505'
                      AND g.nombre ILIKE '%general%')
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE plan_cuenta c
   SET auxiliar = FALSE,
       es_medio_pago = FALSE
 WHERE c.codigo = '1105'
   AND EXISTS (SELECT 1 FROM plan_cuenta g
                WHERE g.empresa_id = c.empresa_id AND g.codigo = '110505'
                  AND g.nombre ILIKE '%general%')
   AND NOT EXISTS (SELECT 1 FROM cuenta_config x WHERE x.cuenta_id = c.id)
   AND NOT EXISTS (SELECT 1 FROM forma_pago_contable f WHERE f.cuenta_contable_id = c.id)
   AND NOT EXISTS (SELECT 1 FROM cuenta_bancaria b WHERE b.cuenta_contable_id = c.id)
MIG_SQL);
    }

    public function down(): void
    {
        // Irreversible: los documentos posteriores ya se contabilizaron en 110505/110510.
    }
};
