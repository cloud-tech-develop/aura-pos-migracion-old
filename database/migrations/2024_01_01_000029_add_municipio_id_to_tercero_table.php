<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tercero', function (Blueprint $table) {
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->after('direccion');
        });
    }

    public function down(): void
    {
        Schema::table('tercero', function (Blueprint $table) {
            $table->dropForeign(['municipio_id']);
            $table->dropColumn('municipio_id');
        });
    }
};
