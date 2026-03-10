<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizacion', function (Blueprint $table) {
            $table->integer('dias_vigencia')->default(3)->nullable()->after('fecha_vencimiento');
        });
    }

    public function down(): void
    {
        Schema::table('cotizacion', function (Blueprint $table) {
            $table->dropColumn('dias_vigencia');
        });
    }
};