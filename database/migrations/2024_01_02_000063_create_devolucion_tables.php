<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devolucion', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');
            $table->unsignedBigInteger('venta_id');
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('usuario_id');

            $table->unsignedBigInteger('consecutivo')->nullable();

            $table->string('tipo', 20)->default('PARCIAL');
            $table->string('estado', 20)->default('COMPLETADA');
            $table->string('motivo', 500)->nullable();

            $table->decimal('total_devolucion', 15, 2)->default(0);
            $table->boolean('reintegra_inventario')->default(true);

            $table->string('observaciones', 500)->nullable();

            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->foreign('sucursal_id')->references('id')->on('sucursal');
            $table->foreign('venta_id')->references('id')->on('venta');
            $table->foreign('cliente_id')->references('id')->on('tercero');
            $table->foreign('usuario_id')->references('id')->on('usuario');
        });

        Schema::create('devolucion_detalle', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('devolucion_id');
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('producto_presentacion_id')->nullable();
            $table->unsignedBigInteger('lote_id')->nullable();

            $table->decimal('cantidad', 15, 4);
            $table->decimal('precio_unitario', 15, 2)->default(0);
            $table->decimal('impuesto_valor', 15, 2)->default(0);
            $table->decimal('subtotal_linea', 15, 2)->default(0);

            $table->foreign('devolucion_id')->references('id')->on('devolucion');
            $table->foreign('producto_id')->references('id')->on('producto');
            $table->foreign('producto_presentacion_id')->references('id')->on('producto_presentacion');
            $table->foreign('lote_id')->references('id')->on('lote');
        });

        Schema::table('venta', function (Blueprint $table) {
            $table->string('estado_devolucion', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('venta', function (Blueprint $table) {
            $table->dropColumn('estado_devolucion');
        });

        Schema::dropIfExists('devolucion_detalle');
        Schema::dropIfExists('devolucion');
    }
};