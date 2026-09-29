<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V66: Tesorería contable — cuenta de contrapartida en el movimiento. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tesoreria_movimiento', function (Blueprint $table) {
            if (!Schema::hasColumn('tesoreria_movimiento', 'contrapartida_cuenta_id')) {
                $table->unsignedBigInteger('contrapartida_cuenta_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tesoreria_movimiento', function (Blueprint $table) {
            $table->dropColumn('contrapartida_cuenta_id');
        });
    }
};
