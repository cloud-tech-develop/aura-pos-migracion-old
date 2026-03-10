<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimiento_caja', function (Blueprint $table) {
            if (!Schema::hasColumn('movimiento_caja', 'cuenta_cobrar_id')) {
                $table->unsignedBigInteger('cuenta_cobrar_id')->nullable();
                $table->foreign('cuenta_cobrar_id')
                      ->references('id')
                      ->on('cuentas_cobrar')
                      ->onDelete('set null');
                $table->index('cuenta_cobrar_id', 'idx_movimiento_caja_cuenta_cobrar');
            }
            if (!Schema::hasColumn('movimiento_caja', 'cuenta_pagar_id')) {
                $table->unsignedBigInteger('cuenta_pagar_id')->nullable();
                $table->foreign('cuenta_pagar_id')
                      ->references('id')
                      ->on('cuentas_pagar')
                      ->onDelete('set null');
                $table->index('cuenta_pagar_id', 'idx_movimiento_caja_cuenta_pagar');
            }
        });
    }

    public function down(): void
    {
        Schema::table('movimiento_caja', function (Blueprint $table) {
            $table->dropForeign(['cuenta_cobrar_id']);
            $table->dropIndex('idx_movimiento_caja_cuenta_cobrar');
            $table->dropColumn('cuenta_cobrar_id');

            $table->dropForeign(['cuenta_pagar_id']);
            $table->dropIndex('idx_movimiento_caja_cuenta_pagar');
            $table->dropColumn('cuenta_pagar_id');
        });
    }
};
