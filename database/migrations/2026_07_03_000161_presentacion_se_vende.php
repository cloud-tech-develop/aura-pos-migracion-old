<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * V161 — presentación que se compra pero no se vende.
 *
 * Traducción de la migración Flyway V161__presentacion_se_vende.sql (aura-back-old).
 *
 * se_vende = false → sirve para comprar, pero el POS no la ofrece.
 * Las existentes quedan en true, que es como se comportaban. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('producto_presentacion', 'se_vende')) {
            DB::statement(<<<'MIG_SQL'
ALTER TABLE producto_presentacion
    ADD COLUMN se_vende BOOLEAN NOT NULL DEFAULT true
MIG_SQL);
        }

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN producto_presentacion.se_vende IS
    'false = solo para comprar: el POS no la ofrece. true (por defecto) = también se vende.'
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE producto_presentacion DROP COLUMN IF EXISTS se_vende');
    }
};
