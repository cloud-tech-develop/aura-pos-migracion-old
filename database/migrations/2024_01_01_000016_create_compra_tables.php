<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('compra')) {
            Schema::create('compra', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->nullable()->constrained('empresa');
                $table->foreignId('sucursal_id')->nullable()->constrained('sucursal');
                $table->foreignId('proveedor_id')->nullable()->constrained('tercero');
                $table->foreignId('usuario_id')->nullable()->constrained('usuario');
                $table->string('numero_compra', 50)->nullable();
                $table->timestamp('fecha')->useCurrent();
                $table->decimal('subtotal', 14, 2)->nullable();
                $table->decimal('descuento_total', 14, 2)->default(0);
                $table->decimal('impuestos_total', 14, 2)->default(0);
                $table->decimal('total', 14, 2)->nullable();
                $table->text('observaciones')->nullable();
                $table->string('estado', 20)->default('RECIBIDA'); // RECIBIDA, ANULADA
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('compra_detalle')) {
            Schema::create('compra_detalle', function (Blueprint $table) {
                $table->id();
                $table->foreignId('compra_id')->nullable()->constrained('compra');
                $table->foreignId('producto_id')->nullable()->constrained('producto');
                $table->foreignId('lote_id')->nullable()->constrained('lote');
                $table->decimal('cantidad', 14, 4);
                $table->decimal('costo_unitario', 14, 2);
                $table->decimal('impuesto_valor', 14, 2)->default(0);
                $table->decimal('subtotal_linea', 14, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_detalle');
        Schema::dropIfExists('compra');
    }
};
