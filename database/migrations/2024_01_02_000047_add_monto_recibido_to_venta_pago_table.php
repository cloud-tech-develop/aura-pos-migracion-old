<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venta_pago', function (Blueprint $table) {
            if (!Schema::hasColumn('venta_pago', 'monto_recibido')) {
                $table->decimal('monto_recibido', 15, 2)->nullable()->after('monto');
            }
        });
    }

    public function down(): void
    {
        Schema::table('venta_pago', function (Blueprint $table) {
            $table->dropColumn('monto_recibido');
        });
    }
};