<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venta_pago', function (Blueprint $table) {
            $table->unsignedBigInteger('cuenta_bancaria_id')->nullable()->after('referencia');
            $table->foreign('cuenta_bancaria_id', 'fk_venta_pago_cuenta_bancaria')
                ->references('id')->on('cuenta_bancaria');
        });
    }

    public function down(): void
    {
        Schema::table('venta_pago', function (Blueprint $table) {
            $table->dropForeign('fk_venta_pago_cuenta_bancaria');
            $table->dropColumn('cuenta_bancaria_id');
        });
    }
};