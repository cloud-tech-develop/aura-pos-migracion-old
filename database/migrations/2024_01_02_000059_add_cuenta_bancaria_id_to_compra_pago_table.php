<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compra_pago', function (Blueprint $table) {
            $table->unsignedBigInteger('cuenta_bancaria_id')->nullable()->after('banco');
            $table->foreign('cuenta_bancaria_id')->references('id')->on('cuenta_bancaria');
            $table->index('cuenta_bancaria_id', 'idx_compra_pago_cuenta');
        });
    }

    public function down(): void
    {
        Schema::table('compra_pago', function (Blueprint $table) {
            $table->dropForeign(['cuenta_bancaria_id']);
            $table->dropIndex('idx_compra_pago_cuenta');
            $table->dropColumn('cuenta_bancaria_id');
        });
    }
};