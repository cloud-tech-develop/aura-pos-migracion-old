<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V247 — nomina_detalle.novedad_id con ON DELETE SET NULL: reliquidar o quitar una
 * novedad ya no choca con las líneas de la liquidación anterior.
 *
 * Traducción de la migración Flyway V247__nomina_detalle_novedad_set_null.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE nomina_detalle DROP CONSTRAINT IF EXISTS nomina_detalle_novedad_id_fkey');

        DB::statement(<<<'MIG_SQL'
ALTER TABLE nomina_detalle
    ADD CONSTRAINT nomina_detalle_novedad_id_fkey
    FOREIGN KEY (novedad_id) REFERENCES nomina_novedad(id) ON DELETE SET NULL
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE nomina_detalle DROP CONSTRAINT IF EXISTS nomina_detalle_novedad_id_fkey');

        DB::statement('ALTER TABLE nomina_detalle ADD CONSTRAINT nomina_detalle_novedad_id_fkey FOREIGN KEY (novedad_id) REFERENCES nomina_novedad(id)');
    }
};
