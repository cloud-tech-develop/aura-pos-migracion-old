<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V175 — Código de la cuenta bancaria (CB-001, CB-002…), único por empresa.
 * Las cuentas existentes reciben su código en orden de creación.
 *
 * Traducción de la migración Flyway V175__cuenta_bancaria_codigo.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE cuenta_bancaria ADD COLUMN IF NOT EXISTS codigo VARCHAR(20)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE cuenta_bancaria cb
   SET codigo = t.codigo
  FROM (SELECT id,
               'CB-' || LPAD(ROW_NUMBER() OVER (PARTITION BY empresa_id ORDER BY id)::text, 3, '0') AS codigo
          FROM cuenta_bancaria
         WHERE codigo IS NULL) t
 WHERE cb.id = t.id
   AND NOT EXISTS (SELECT 1 FROM cuenta_bancaria x
                    WHERE x.empresa_id = cb.empresa_id AND x.codigo = t.codigo)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS ux_cuenta_bancaria_codigo
    ON cuenta_bancaria (empresa_id, UPPER(codigo))
    WHERE codigo IS NOT NULL
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ux_cuenta_bancaria_codigo');
        DB::statement('ALTER TABLE cuenta_bancaria DROP COLUMN IF EXISTS codigo');
    }
};
