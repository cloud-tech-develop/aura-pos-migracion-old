<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comision_liquidacion', function (Blueprint $table) {
            if (!Schema::hasColumn('comision_liquidacion', 'metodo_pago')) {
                $table->string('metodo_pago', 30)->nullable()->after('fecha_pago');
            }
            if (!Schema::hasColumn('comision_liquidacion', 'cuenta_bancaria_id')) {
                $table->unsignedBigInteger('cuenta_bancaria_id')->nullable()->after('metodo_pago');
            }
            $table->foreign('cuenta_bancaria_id')->references('id')->on('cuenta_bancaria');
        });
    }

    public function down(): void
    {
        
        Schema::table('comision_liquidacion', function (Blueprint $table) {
            $table->dropForeign(['cuenta_bancaria_id']);
            $table->dropColumn(['metodo_pago', 'cuenta_bancaria_id']);
        });
    }
};