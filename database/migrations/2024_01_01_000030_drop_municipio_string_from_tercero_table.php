<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tercero', function (Blueprint $table) {
            $table->dropColumn('municipio');
        });
    }

    public function down(): void
    {
        Schema::table('tercero', function (Blueprint $table) {
            $table->string('municipio', 255)->nullable()->after('direccion');
        });
    }
};
