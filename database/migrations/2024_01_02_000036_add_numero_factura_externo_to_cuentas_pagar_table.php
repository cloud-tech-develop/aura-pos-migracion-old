<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cuentas_pagar', function (Blueprint $table) {
            if (!Schema::hasColumn('cuentas_pagar', 'numero_factura_externo')) {
                $table->string('numero_factura_externo', 50)->nullable()->after('numero_cuenta');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuentas_pagar', function (Blueprint $table) {
            if (Schema::hasColumn('cuentas_pagar', 'numero_factura_externo')) {
                $table->dropColumn('numero_factura_externo');
            }
        });
    }
};
