<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activo_fijo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->string('codigo', 30);
            $table->string('descripcion', 200);
            $table->string('categoria', 50);
            $table->date('fecha_adquisicion');
            $table->decimal('valor_compra', 18, 2);
            $table->integer('vida_util_meses');
            $table->string('metodo_depreciacion', 20)->default('LINEA_RECTA');
            $table->decimal('depreciacion_acumulada', 18, 2)->default(0);
            $table->decimal('valor_residual', 18, 2)->default(0);
            $table->string('ubicacion', 100)->nullable();
            $table->string('responsable', 100)->nullable();
            $table->string('estado', 20)->default('ACTIVO');
            $table->unsignedBigInteger('cuenta_activo_id')->nullable();
            $table->unsignedBigInteger('cuenta_depreciacion_id')->nullable();
            $table->unsignedBigInteger('cuenta_gasto_dep_id')->nullable();
            $table->unsignedBigInteger('centro_costo_id')->nullable();
            $table->unsignedBigInteger('periodo_contable_id')->nullable();
            $table->unsignedBigInteger('tercero_id')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->foreign('cuenta_activo_id')->references('id')->on('plan_cuenta');
            $table->foreign('cuenta_depreciacion_id')->references('id')->on('plan_cuenta');
            $table->foreign('cuenta_gasto_dep_id')->references('id')->on('plan_cuenta');
            $table->foreign('centro_costo_id')->references('id')->on('centros_costos');
            $table->foreign('periodo_contable_id')->references('id')->on('periodo_contable');
            $table->foreign('tercero_id')->references('id')->on('tercero');

            $table->unique(['empresa_id', 'codigo'], 'uq_activo_empresa_codigo');
        });

        Schema::create('depreciacion_periodo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('activo_id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedBigInteger('periodo_id');
            $table->decimal('valor', 18, 2);
            $table->unsignedBigInteger('asiento_id')->nullable();
            $table->timestamp('calculado_en')->useCurrent();

            $table->foreign('activo_id')->references('id')->on('activo_fijo');
            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->foreign('periodo_id')->references('id')->on('periodo_contable');
            $table->foreign('asiento_id')->references('id')->on('asiento_contable');
        });

        Schema::create('tarifa_retencion', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->string('tipo', 20);
            $table->string('concepto', 100);
            $table->string('codigo_concepto', 20)->nullable();
            $table->decimal('tarifa_natural', 5, 2);
            $table->decimal('tarifa_juridica', 5, 2);
            $table->decimal('base_minima', 18, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('empresa_id')->references('id')->on('empresa');

            $table->unique(['empresa_id', 'tipo', 'concepto'], 'uq_tarifa_empresa_tipo_concepto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifa_retencion');
        Schema::dropIfExists('depreciacion_periodo');
        Schema::dropIfExists('activo_fijo');
    }
};