<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compra', function (Blueprint $table) {
            $table->decimal('retefuente_pct', 5, 2)->default(0)->nullable()->after('total');
            $table->decimal('retefuente_valor', 15, 2)->default(0)->nullable()->after('retefuente_pct');
            $table->decimal('reteiva_pct', 5, 2)->default(0)->nullable()->after('retefuente_valor');
            $table->decimal('reteiva_valor', 15, 2)->default(0)->nullable()->after('reteiva_pct');
            $table->decimal('reteica_pct', 5, 2)->default(0)->nullable()->after('reteiva_valor');
            $table->decimal('reteica_valor', 15, 2)->default(0)->nullable()->after('reteica_pct');
            $table->decimal('total_retenciones', 15, 2)->default(0)->nullable()->after('reteica_valor');
            $table->decimal('neto_a_pagar', 15, 2)->nullable()->after('total_retenciones');
            $table->string('forma_pago', 20)->default('CONTADO')->nullable()->after('neto_a_pagar');
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