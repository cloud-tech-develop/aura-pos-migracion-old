<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V132 — empresa: rango de numeración de nómina electrónica de Factus.
 *
 * El ULID (documento tipo 26) que Factus le asigna a cada NIT para nómina. Va
 * por empresa, al lado del rango de facturación. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            if (!Schema::hasColumn('empresa', 'factus_nomina_numbering_range_id')) {
                $table->string('factus_nomina_numbering_range_id', 40)->nullable();
            }
        });

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN empresa.factus_nomina_numbering_range_id IS
    'Rango de numeración de nómina electrónica (ULID) que Factus asigna a esta empresa. GET /v2/numbering-ranges?filter[document]=26.'
MIG_SQL);
    }

    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            if (Schema::hasColumn('empresa', 'factus_nomina_numbering_range_id')) {
                $table->dropColumn('factus_nomina_numbering_range_id');
            }
        });
    }
};
