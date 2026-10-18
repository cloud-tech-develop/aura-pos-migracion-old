<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto', function (Blueprint $table) {
            if (!Schema::hasColumn('producto', 'precio_2')) {
                $table->decimal('precio_2', 15, 2)->nullable()->after('precio');
            }
            if (!Schema::hasColumn('producto', 'precio_3')) {
                $table->decimal('precio_3', 15, 2)->nullable()->after('precio_2');
            }
        });

        Schema::table('compra_detalle', function (Blueprint $table) {
            if (!Schema::hasColumn('compra_detalle', 'precio_venta1')) {
                $table->decimal('precio_venta1', 15, 2)->nullable()->after('descuento_valor');
            }
            if (!Schema::hasColumn('compra_detalle', 'precio_venta2')) {
                $table->decimal('precio_venta2', 15, 2)->nullable()->after('precio_venta1');
            }
            if (!Schema::hasColumn('compra_detalle', 'precio_venta3')) {
                $table->decimal('precio_venta3', 15, 2)->nullable()->after('precio_venta2');
            }
        });
    }

    public function down(): void
    {
        Schema::table('producto', function (Blueprint $table) {
            $table->dropColumn(['precio_2', 'precio_3']);
        });

        Schema::table('compra_detalle', function (Blueprint $table) {
            $table->dropColumn(['precio_venta1', 'precio_venta2', 'precio_venta3']);
        });
    }
};