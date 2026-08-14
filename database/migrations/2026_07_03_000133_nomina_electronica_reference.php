<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V133 — nómina electrónica: persistir el reference_code de Factus.
 *
 * Traducción fiel de la migración Flyway V133__nomina_electronica_reference.sql
 * (aura-back-old): cada sentencia de PostgreSQL va en su propio DB::statement.
 * Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- Factus identifica cada documento por su reference_code (el que enviamos en el
-- POST /v2/payrolls y con el que se consulta/elimina en GET|DELETE
-- /v2/payrolls/reference/{ref}). Guardarlo permite ubicar la fila local por
-- referencia sin reconstruirla parseando el id de la nómina. El CUNE ya se
-- persiste (V109); esto agrega su compañera, la referencia.
--
-- Seguro: solo agrega una columna nueva, nullable (las filas viejas quedan sin
-- referencia y siguen resolviéndose por nomina_id).

ALTER TABLE nomina_electronica
    ADD COLUMN IF NOT EXISTS reference_code VARCHAR(100)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Una referencia identifica un único documento por empresa. Un reintento reusa
-- la misma fila con la misma referencia (no choca); un ajuste lleva otra.
CREATE UNIQUE INDEX IF NOT EXISTS ux_ne_reference
    ON nomina_electronica(empresa_id, reference_code)
    WHERE reference_code IS NOT NULL
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ux_ne_reference');
        DB::statement('ALTER TABLE nomina_electronica DROP COLUMN IF EXISTS reference_code');
    }
};
