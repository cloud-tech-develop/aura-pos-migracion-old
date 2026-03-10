<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('factura')) {
            Schema::create('factura', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('prefijo', 10);
                $table->unsignedBigInteger('consecutivo');
                $table->string('metodo_pago', 50)->comment('EFECTIVO, TRANSFERENCIA, TARJETA, CREDITO');
                $table->decimal('valor', 12, 2);
                $table->decimal('descuento', 12, 2)->default(0);
                $table->unsignedInteger('usuario_id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('venta_id');
                $table->string('cufe', 100)->unique();
                $table->string('descripcion', 300)->nullable();
                $table->timestamp('fecha_hora_emision')->useCurrent();
                $table->string('estado_dian', 50)->default('PENDIENTE')
                    ->comment('PENDIENTE, AUTORIZADO, RECHAZADO, ERROR');
                $table->string('tipo_ambiente', 10)->default('dev')
                    ->comment('dev, prod');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('deleted_at')->nullable();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->foreign('usuario_id')->references('id')->on('usuario');
                $table->foreign('venta_id')->references('id')->on('venta');
                $table->unique(['empresa_id', 'prefijo', 'consecutivo'], 'factura_prefijo_consecutivo_unique');
            });

            // Índices
            Schema::table('factura', function (Blueprint $table) {
                $table->index('empresa_id', 'idx_factura_empresa');
                $table->index('venta_id', 'idx_factura_venta');
                $table->index('cufe', 'idx_factura_cufe');
                $table->index('deleted_at', 'idx_factura_deleted_at');
            });
        }

        if (!Schema::hasTable('factura_pago')) {
            Schema::create('factura_pago', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('factura_id');
                $table->decimal('valor', 12, 2);
                $table->string('banco', 300)->nullable();
                $table->string('tipo', 50)->nullable()->comment('credito, debito');
                $table->string('descripcion', 300)->nullable();
                $table->unsignedInteger('usuario_id');
                $table->string('metodo_pago', 50);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();

                $table->foreign('factura_id', 'factura_pago_factura_fk')
                    ->references('id')->on('factura')->onDelete('cascade');
                $table->index('factura_id', 'idx_factura_pago_factura');
            });
        }

        if (!Schema::hasTable('nota_contable')) {
            Schema::create('nota_contable', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('factura_id');
                $table->decimal('valor', 12, 2);
                $table->string('banco', 300)->nullable();
                $table->integer('tipo')->comment('1 o 2');
                $table->string('nota', 500);
                $table->unsignedInteger('usuario_id');
                $table->string('metodo_pago', 50);
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('factura_id', 'nota_contable_factura_fk')
                    ->references('id')->on('factura')->onDelete('cascade');
                $table->index('factura_id', 'idx_nota_contable_factura');
                $table->index('tipo', 'idx_nota_contable_tipo');
                $table->index('created_at', 'idx_nota_contable_created_at');
            });
        }

        if (!Schema::hasTable('recibo_pago')) {
            Schema::create('recibo_pago', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('factura_id')->nullable();
                $table->decimal('valor', 19, 2)->nullable();
                $table->string('banco', 255)->nullable();
                $table->string('tipo', 255)->nullable();
                $table->text('descripcion')->nullable();
                $table->unsignedInteger('usuario_id')->nullable();
                $table->string('metodo_pago', 255)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->foreign('factura_id', 'fk_pago_factura')
                    ->references('id')->on('factura')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('factura_log')) {
            Schema::create('factura_log', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('factura_id');
                $table->string('evento', 50);
                $table->string('estado_anterior', 50)->nullable();
                $table->string('estado_nuevo', 50)->nullable();
                $table->jsonb('datos')->nullable();
                $table->unsignedInteger('usuario_id')->nullable();
                $table->string('mensaje', 500)->nullable();
                $table->jsonb('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('factura_id', 'idx_factura_log_factura_fk')
                    ->references('id')->on('factura');
                $table->index('factura_id', 'idx_factura_log_factura');
                $table->index('evento', 'idx_factura_log_evento');
                $table->index('created_at', 'idx_factura_log_created_at');
                $table->index(['factura_id', 'created_at'], 'idx_factura_log_factura_fecha');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_log');
        Schema::dropIfExists('recibo_pago');
        Schema::dropIfExists('nota_contable');
        Schema::dropIfExists('factura_pago');
        Schema::dropIfExists('factura');
    }
};
