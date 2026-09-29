<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gasto', function (Blueprint $table) {
            $table->unsignedBigInteger('tercero_id')->nullable()->after('usuario_id');
            $table->unsignedBigInteger('cuenta_contable_id')->nullable()->after('tercero_id');
            $table->unsignedBigInteger('centro_costo_id')->nullable()->after('cuenta_contable_id');
            $table->decimal('base_iva', 18, 2)->default(0)->after('centro_costo_id');
            $table->decimal('tarifa_iva', 5, 2)->default(0)->after('base_iva');
            $table->decimal('valor_iva', 18, 2)->default(0)->after('tarifa_iva');
            $table->decimal('base_retefuente', 18, 2)->default(0)->after('valor_iva');
            $table->decimal('tarifa_retefuente', 5, 2)->default(0)->after('base_retefuente');
            $table->decimal('valor_retefuente', 18, 2)->default(0)->after('tarifa_retefuente');
            $table->decimal('base_reteica', 18, 2)->default(0)->after('valor_retefuente');
            $table->decimal('tarifa_reteica', 5, 2)->default(0)->after('base_reteica');
            $table->decimal('valor_reteica', 18, 2)->default(0)->after('tarifa_reteica');
            $table->string('tipo_doc_soporte', 20)->nullable()->after('valor_reteica');
            $table->string('numero_doc_soporte', 50)->nullable()->after('tipo_doc_soporte');
            $table->unsignedBigInteger('periodo_contable_id')->nullable()->after('numero_doc_soporte');

            $table->foreign('tercero_id')->references('id')->on('tercero');
            $table->foreign('cuenta_contable_id')->references('id')->on('plan_cuenta');
            $table->foreign('centro_costo_id')->references('id')->on('centros_costos');
            $table->foreign('periodo_contable_id')->references('id')->on('periodo_contable');
        });
    }

    public function down(): void
    {
        Schema::table('gasto', function (Blueprint $table) {
            $table->dropForeign(['tercero_id']);
            $table->dropForeign(['cuenta_contable_id']);
            $table->dropForeign(['centro_costo_id']);
            $table->dropForeign(['periodo_contable_id']);
            $table->dropColumn([
                'tercero_id', 'cuenta_contable_id', 'centro_costo_id',
                'base_iva', 'tarifa_iva', 'valor_iva',
                'base_retefuente', 'tarifa_retefuente', 'valor_retefuente',
                'base_reteica', 'tarifa_reteica', 'valor_reteica',
                'tipo_doc_soporte', 'numero_doc_soporte', 'periodo_contable_id',
            ]);
        });
    }
};