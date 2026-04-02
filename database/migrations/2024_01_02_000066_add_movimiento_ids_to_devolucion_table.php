<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devolucion', function (Blueprint $table) {
            $table->unsignedBigInteger('movimiento_caja_id')->nullable()->after('monto_cartera_afectado');
            $table->unsignedBigInteger('tesoreria_movimiento_id')->nullable()->after('movimiento_caja_id');

            $table->foreign('movimiento_caja_id')->references('id')->on('movimiento_caja');
            $table->foreign('tesoreria_movimiento_id')->references('id')->on('tesoreria_movimiento');
        });

        DB::statement("COMMENT ON COLUMN devolucion.movimiento_caja_id IS 'FK al movimiento_caja generado al devolver en efectivo'");
        DB::statement("COMMENT ON COLUMN devolucion.tesoreria_movimiento_id IS 'FK al tesoreria_movimiento generado al devolver dinero'");
    }

    public function down(): void
    {
        Schema::table('devolucion', function (Blueprint $table) {
            $table->dropForeign(['movimiento_caja_id']);
            $table->dropForeign(['tesoreria_movimiento_id']);
            $table->dropColumn(['movimiento_caja_id', 'tesoreria_movimiento_id']);
        });
    }
};