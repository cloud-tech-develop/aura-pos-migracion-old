<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comision_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa');
            $table->foreignId('producto_id')->constrained('producto');
            $table->foreignId('tecnico_id')->nullable()->constrained('usuario');
            $table->string('tipo', 20)->default('PORCENTAJE'); // PORCENTAJE | VALOR_FIJO
            $table->decimal('porcentaje_tecnico', 5, 2);
            $table->decimal('porcentaje_negocio', 5, 2);
            $table->boolean('activo')->default(true);

            $table->index('empresa_id', 'idx_comision_config_empresa');
            $table->index(['producto_id', 'empresa_id', 'activo'], 'idx_comision_config_producto');
        });

        // CHECK constraint porcentajes suman 100
        DB::statement('ALTER TABLE comision_config ADD CONSTRAINT chk_porcentajes CHECK (porcentaje_tecnico + porcentaje_negocio = 100)');

        Schema::create('comision_liquidacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa');
            $table->foreignId('tecnico_id')->constrained('usuario');
            $table->date('fecha_desde');
            $table->date('fecha_hasta');
            $table->integer('total_servicios')->default(0);
            $table->decimal('valor_total', 14, 2)->default(0);
            $table->string('estado', 20)->default('PENDIENTE');
            $table->text('observaciones')->nullable();
            $table->date('fecha_pago')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('empresa_id', 'idx_comision_liquidacion_empresa');
            $table->index(['tecnico_id', 'estado'], 'idx_comision_liquidacion_tecnico');
        });

        Schema::create('comision_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa');
            $table->unsignedBigInteger('venta_id');
            $table->unsignedBigInteger('venta_detalle_id');
            $table->unsignedBigInteger('producto_id');
            $table->foreignId('tecnico_id')->nullable()->constrained('usuario');
            $table->decimal('valor_total', 14, 2);
            $table->decimal('porcentaje_tecnico', 5, 2);
            $table->decimal('porcentaje_negocio', 5, 2);
            $table->decimal('valor_tecnico', 14, 2);
            $table->decimal('valor_negocio', 14, 2);
            $table->foreignId('liquidacion_id')->nullable()->constrained('comision_liquidacion');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('venta_id')->references('id')->on('venta');
            $table->foreign('venta_detalle_id')->references('id')->on('venta_detalle');
            $table->foreign('producto_id')->references('id')->on('producto');

            $table->index('empresa_id', 'idx_comision_venta_empresa');
            $table->index(['tecnico_id', 'empresa_id', 'liquidacion_id'], 'idx_comision_venta_tecnico_pendiente');
            $table->index('liquidacion_id', 'idx_comision_venta_liquidacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comision_venta');
        Schema::dropIfExists('comision_liquidacion');
        Schema::dropIfExists('comision_config');
    }
};