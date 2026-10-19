<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compra', function (Blueprint $table) {
            if (!Schema::hasColumn('compra', 'retefuente_pct')) {
                $table->decimal('retefuente_pct', 5, 2)->default(0)->nullable()->after('total');
            }
            if (!Schema::hasColumn('compra', 'retefuente_valor')) {
                $table->decimal('retefuente_valor', 15, 2)->default(0)->nullable()->after('retefuente_pct');
            }
            if (!Schema::hasColumn('compra', 'reteiva_pct')) {
                $table->decimal('reteiva_pct', 5, 2)->default(0)->nullable()->after('retefuente_valor');
            }
            if (!Schema::hasColumn('compra', 'reteiva_valor')) {
                $table->decimal('reteiva_valor', 15, 2)->default(0)->nullable()->after('reteiva_pct');
            }
            if (!Schema::hasColumn('compra', 'reteica_pct')) {
                $table->decimal('reteica_pct', 5, 2)->default(0)->nullable()->after('reteiva_valor');
            }
            if (!Schema::hasColumn('compra', 'reteica_valor')) {
                $table->decimal('reteica_valor', 15, 2)->default(0)->nullable()->after('reteica_pct');
            }
            if (!Schema::hasColumn('compra', 'total_retenciones')) {
                $table->decimal('total_retenciones', 15, 2)->default(0)->nullable()->after('reteica_valor');
            }
            if (!Schema::hasColumn('compra', 'neto_a_pagar')) {
                $table->decimal('neto_a_pagar', 15, 2)->nullable()->after('total_retenciones');
            }
            if (!Schema::hasColumn('compra', 'forma_pago')) {
                $table->string('forma_pago', 20)->default('CONTADO')->nullable()->after('neto_a_pagar');
            }
        });
    }

    public function down(): void
    {
        Schema::table('compra', function (Blueprint $table) {
            $table->dropColumn([
                'retefuente_pct',
                'retefuente_valor',
                'reteiva_pct',
                'reteiva_valor',
                'reteica_pct',
                'reteica_valor',
                'total_retenciones',
                'neto_a_pagar',
                'forma_pago',
            ]);
        });
    }
};