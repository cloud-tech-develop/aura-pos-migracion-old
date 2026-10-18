<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            if (!Schema::hasColumn('empresa', 'telefono')) {
                $table->string('telefono', 30)->nullable()->after('nit');
            }
            if (!Schema::hasColumn('empresa', 'municipio')) {
                $table->string('municipio', 200)->nullable()->after('telefono');
            }
            if (!Schema::hasColumn('empresa', 'municipio_id')) {
                $table->integer('municipio_id')->nullable()->after('municipio');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->dropColumn(['telefono', 'municipio', 'municipio_id']);
        });
    }
};