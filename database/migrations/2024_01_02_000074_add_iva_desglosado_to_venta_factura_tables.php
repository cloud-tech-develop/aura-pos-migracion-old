<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venta', function (Blueprint $table) {
            if (!Schema::hasColumn('venta', 'iva_base0')) {
                $table->decimal('iva_base0', 18, 2)->default(0)->after('impuestos_total');
            }
            if (!Schema::hasColumn('venta', 'iva_base5')) {
                $table->decimal('iva_base5', 18, 2)->default(0)->after('iva_base0');
            }
            if (!Schema::hasColumn('venta', 'iva_valor5')) {
                $table->decimal('iva_valor5', 18, 2)->default(0)->after('iva_base5');
            }
            if (!Schema::hasColumn('venta', 'iva_base19')) {
                $table->decimal('iva_base19', 18, 2)->default(0)->after('iva_valor5');
            }
            if (!Schema::hasColumn('venta', 'iva_valor19')) {
                $table->decimal('iva_valor19', 18, 2)->default(0)->after('iva_base19');
            }
        });

        Schema::table('factura', function (Blueprint $table) {
            if (!Schema::hasColumn('factura', 'iva_base0')) {
                $table->decimal('iva_base0', 18, 2)->default(0)->after('descuento');
            }
            if (!Schema::hasColumn('factura', 'iva_base5')) {
                $table->decimal('iva_base5', 18, 2)->default(0)->after('iva_base0');
            }
            if (!Schema::hasColumn('factura', 'iva_valor5')) {
                $table->decimal('iva_valor5', 18, 2)->default(0)->after('iva_base5');
            }
            if (!Schema::hasColumn('factura', 'iva_base19')) {
                $table->decimal('iva_base19', 18, 2)->default(0)->after('iva_valor5');
            }
            if (!Schema::hasColumn('factura', 'iva_valor19')) {
                $table->decimal('iva_valor19', 18, 2)->default(0)->after('iva_base19');
            }
        });
    }

    public function down(): void
    {
        $campos = ['iva_base0', 'iva_base5', 'iva_valor5', 'iva_base19', 'iva_valor19'];

        Schema::table('venta', function (Blueprint $table) use ($campos) {
            $table->dropColumn($campos);
        });

        Schema::table('factura', function (Blueprint $table) use ($campos) {
            $table->dropColumn($campos);
        });
    }
};