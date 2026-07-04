<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V65: Contabilización del movimiento de caja (concepto + método de pago). Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimiento_caja', function (Blueprint $table) {
            if (!Schema::hasColumn('movimiento_caja', 'concepto_caja_id')) $table->unsignedBigInteger('concepto_caja_id')->nullable();
            if (!Schema::hasColumn('movimiento_caja', 'metodo_pago'))      $table->string('metodo_pago', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('movimiento_caja', function (Blueprint $table) {
            $table->dropColumn(['concepto_caja_id', 'metodo_pago']);
        });
    }
};
