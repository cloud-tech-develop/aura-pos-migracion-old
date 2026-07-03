<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locales', function (Blueprint $table) {
            $table->string('ciudad', 100)->nullable()->after('direccion');
            $table->integer('ciudad_id')->nullable()->after('ciudad');
            $table->string('barrio', 100)->nullable()->after('ciudad_id');
        });
    }

    public function down(): void
    {
        Schema::table('locales', function (Blueprint $table) {
            $table->dropColumn(['ciudad', 'ciudad_id', 'barrio']);
        });
    }
};