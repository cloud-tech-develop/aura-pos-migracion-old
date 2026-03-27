<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->string('resolucion_numero', 50)->nullable()->after('factus_token_expiry');
            $table->string('resolucion_prefijo', 20)->nullable()->after('resolucion_numero');
            $table->integer('resolucion_desde')->nullable()->after('resolucion_prefijo');
            $table->integer('resolucion_hasta')->nullable()->after('resolucion_desde');
            $table->string('resolucion_fecha_desde', 20)->nullable()->after('resolucion_hasta');
            $table->string('resolucion_fecha_hasta', 20)->nullable()->after('resolucion_fecha_desde');
        });
    }

    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->dropColumn([
                'resolucion_numero',
                'resolucion_prefijo',
                'resolucion_desde',
                'resolucion_hasta',
                'resolucion_fecha_desde',
                'resolucion_fecha_hasta',
            ]);
        });
    }
};