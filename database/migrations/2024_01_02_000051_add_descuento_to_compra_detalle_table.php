<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compra_detalle', function (Blueprint $table) {
            if (!Schema::hasColumn('compra_detalle', 'descuento_pct')) {
                $table->decimal('descuento_pct', 5, 2)->default(0)->after('impuesto_valor');
            }
            if (!Schema::hasColumn('compra_detalle', 'descuento_valor')) {
                $table->decimal('descuento_valor', 15, 2)->default(0)->after('descuento_pct');
            }
        });
    }

    public function down(): void
    {
        Schema::table('compra_detalle', function (Blueprint $table) {
            $table->dropColumn(['descuento_pct', 'descuento_valor']);
        });
    }
};