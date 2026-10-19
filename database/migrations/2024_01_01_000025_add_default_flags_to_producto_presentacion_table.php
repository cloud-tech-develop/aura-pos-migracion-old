<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto_presentacion', function (Blueprint $table) {
            if (!Schema::hasColumn('producto_presentacion', 'es_default_compra')) {
                $table->boolean('es_default_compra')->default(false)->after('costo');
            }
            if (!Schema::hasColumn('producto_presentacion', 'es_default_venta')) {
                $table->boolean('es_default_venta')->default(false)->after('es_default_compra');
            }
        });
    }

    public function down(): void
    {
        Schema::table('producto_presentacion', function (Blueprint $table) {
            $table->dropColumn(['es_default_compra', 'es_default_venta']);
        });
    }
};