<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V75: Empleado — fecha fin de contrato (contratos a término FIJO). Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            if (!Schema::hasColumn('empleados', 'fecha_fin_contrato')) {
                $table->date('fecha_fin_contrato')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn('fecha_fin_contrato');
        });
    }
};
