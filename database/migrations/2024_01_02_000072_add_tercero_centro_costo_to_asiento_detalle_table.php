<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asiento_detalle', function (Blueprint $table) {
            $table->unsignedBigInteger('tercero_id')->nullable()->after('id');
            $table->unsignedBigInteger('centro_costo_id')->nullable()->after('tercero_id');

            $table->foreign('tercero_id')->references('id')->on('tercero');
            $table->foreign('centro_costo_id')->references('id')->on('centros_costos');
        });
    }

    public function down(): void
    {
        Schema::table('asiento_detalle', function (Blueprint $table) {
            $table->dropForeign(['tercero_id']);
            $table->dropForeign(['centro_costo_id']);
            $table->dropColumn(['tercero_id', 'centro_costo_id']);
        });
    }
};