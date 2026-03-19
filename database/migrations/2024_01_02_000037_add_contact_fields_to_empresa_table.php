<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->string('telefono', 30)->nullable()->after('nit');
            $table->string('municipio', 200)->nullable()->after('telefono');
            $table->integer('municipio_id')->nullable()->after('municipio');
        });
    }

    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->dropColumn(['telefono', 'municipio', 'municipio_id']);
        });
    }
};