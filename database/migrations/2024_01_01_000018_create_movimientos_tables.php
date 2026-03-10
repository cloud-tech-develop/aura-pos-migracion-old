<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('movimiento_inventario')) {
            Schema::create('movimiento_inventario', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sucursal_id')->nullable()->constrained('sucursal');
                $table->foreignId('producto_id')->nullable()->constrained('producto');
                $table->foreignId('lote_id')->nullable()->constrained('lote');
                $table->string('tipo_movimiento', 20)->nullable(); // VENTA, COMPRA, MERMA, TRASLADO
                $table->decimal('cantidad', 14, 4)->nullable();
                $table->decimal('saldo_anterior', 14, 4)->nullable();
                $table->decimal('saldo_nuevo', 14, 4)->nullable();
                $table->decimal('costo_historico', 14, 2)->nullable();
                $table->string('referencia_origen', 100)->nullable(); // "Venta #123"
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('traslado')) {
            Schema::create('traslado', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->nullable()->constrained('empresa');
                $table->unsignedBigInteger('sucursal_origen_id')->nullable();
                $table->unsignedBigInteger('sucursal_destino_id')->nullable();
                $table->foreignId('usuario_id')->nullable()->constrained('usuario');
                $table->timestamp('fecha')->useCurrent();
                $table->text('observacion')->nullable();
                $table->string('estado', 20)->default('PENDIENTE'); // PENDIENTE, COMPLETADO, ANULADO
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('sucursal_origen_id')->references('id')->on('sucursal');
                $table->foreign('sucursal_destino_id')->references('id')->on('sucursal');
            });
        }

        if (!Schema::hasTable('traslado_detalle')) {
            Schema::create('traslado_detalle', function (Blueprint $table) {
                $table->id();
                $table->foreignId('traslado_id')->nullable()->constrained('traslado');
                $table->foreignId('producto_id')->nullable()->constrained('producto');
                $table->foreignId('lote_id')->nullable()->constrained('lote');
                $table->decimal('cantidad', 14, 4);
                $table->decimal('costo_unitario', 14, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('traslado_detalle');
        Schema::dropIfExists('traslado');
        Schema::dropIfExists('movimiento_inventario');
    }
};
