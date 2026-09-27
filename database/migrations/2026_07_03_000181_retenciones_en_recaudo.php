<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V181 — Retenciones que los clientes le practican a la empresa, al recaudar:
 * abonos vinculados (abono_origen_id), retenciones por factura del recibo y
 * subcuentas 135515/135517/135518.
 *
 * Traducción de la migración Flyway V181__retenciones_en_recaudo.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE abonos_cobrar ADD COLUMN IF NOT EXISTS abono_origen_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE abonos_cobrar ADD COLUMN IF NOT EXISTS base_retencion  NUMERIC(15,2)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_abonos_cobrar_origen
    ON abonos_cobrar (abono_origen_id)
    WHERE abono_origen_id IS NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE recibo_caja_aplicacion ADD COLUMN IF NOT EXISTS retenciones NUMERIC(15,2) NOT NULL DEFAULT 0
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id,
                         activa, auxiliar, es_medio_pago, created_at)
SELECT p.empresa_id, v.codigo, v.nombre, 'ACTIVO', 'DEBITO', 4, p.id, TRUE, TRUE, FALSE, now()
  FROM (VALUES ('135515', 'Retención en la fuente'),
               ('135517', 'Impuesto a las ventas retenido'),
               ('135518', 'Impuesto de industria y comercio retenido')) AS v(codigo, nombre)
  JOIN plan_cuenta p ON p.codigo = '1355'
 WHERE NOT EXISTS (SELECT 1 FROM plan_cuenta x
                    WHERE x.empresa_id = p.empresa_id AND x.codigo = v.codigo)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE cuenta_config cc
   SET cuenta_id = s.id,
       updated_at = now()
  FROM plan_cuenta c, plan_cuenta s
 WHERE cc.concepto = 'RETEFUENTE_ASUMIDA'
   AND c.id = cc.cuenta_id
   AND c.codigo = '1355'
   AND s.empresa_id = cc.empresa_id
   AND s.codigo = '135515'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE plan_cuenta c
   SET auxiliar = FALSE
 WHERE c.codigo = '1355'
   AND EXISTS (SELECT 1 FROM plan_cuenta s
                WHERE s.empresa_id = c.empresa_id AND s.codigo = '135515')
   AND NOT EXISTS (SELECT 1 FROM cuenta_config x WHERE x.cuenta_id = c.id)
   AND NOT EXISTS (SELECT 1 FROM asiento_detalle d WHERE d.cuenta_id = c.id)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE recibo_caja_aplicacion DROP COLUMN IF EXISTS retenciones');
        DB::statement('DROP INDEX IF EXISTS idx_abonos_cobrar_origen');
        DB::statement('ALTER TABLE abonos_cobrar DROP COLUMN IF EXISTS base_retencion');
        DB::statement('ALTER TABLE abonos_cobrar DROP COLUMN IF EXISTS abono_origen_id');
    }
};
