<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_compra', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('sucursal_id');
            $table->unsignedBigInteger('proveedor_id');
            $table->unsignedInteger('usuario_id')->nullable();
            $table->string('numero_orden', 20);
            $table->string('estado', 30)->default('BORRADOR');
                // BORRADOR | ENVIADA | CONFIRMADA | RECIBIDA_PARCIAL | CERRADA | ANULADA
            $table->date('fecha');
            $table->date('fecha_entrega_esperada')->nullable();
            $table->text('observaciones')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->unsignedBigInteger('compra_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->foreign('sucursal_id')->references('id')->on('sucursal');
            $table->foreign('proveedor_id')->references('id')->on('tercero');
            $table->foreign('usuario_id')->references('id')->on('usuario');
            $table->foreign('compra_id')->references('id')->on('compra');

            $table->unique(['empresa_id', 'numero_orden']);

            $table->index('empresa_id', 'idx_oc_empresa');
            $table->index('estado', 'idx_oc_estado');
        });

        Schema::create('orden_compra_detalle', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('orden_compra_id');
            $table->unsignedBigInteger('producto_id');
            $table->string('producto_nombre', 500);
            $table->decimal('cantidad', 15, 3);
            $table->decimal('cantidad_recibida', 15, 3)->default(0);
            $table->decimal('costo_unitario', 15, 2);
            $table->decimal('subtotal_linea', 15, 2);

            $table->foreign('orden_compra_id')->references('id')->on('orden_compra');
            $table->foreign('producto_id')->references('id')->on('producto');

            $table->index('orden_compra_id', 'idx_ocd_orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_compra_detalle');
        Schema::dropIfExists('orden_compra');
    }
};