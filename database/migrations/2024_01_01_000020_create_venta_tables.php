<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('venta')) {
            Schema::create('venta', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->nullable()->constrained('empresa');
                $table->foreignId('sucursal_id')->nullable()->constrained('sucursal');
                $table->foreignId('cliente_id')->nullable()->constrained('tercero');
                $table->foreignId('usuario_id')->nullable()->constrained('usuario');
                $table->foreignId('turno_caja_id')->nullable()->constrained('turno_caja');

                // Documento
                $table->string('tipo_documento', 20)->default('POS');
                $table->string('prefijo', 10)->nullable();
                $table->unsignedBigInteger('consecutivo')->nullable();
                $table->timestamp('fecha_emision')->useCurrent();
                $table->date('fecha_vencimiento')->nullable();

                // Valores
                $table->decimal('subtotal', 14, 2)->nullable();
                $table->decimal('descuento_total', 14, 2)->nullable();
                $table->decimal('impuestos_total', 14, 2)->nullable();
                $table->decimal('total_pagar', 14, 2)->nullable();

                // Facturación Electrónica
                $table->string('cufe', 255)->nullable();
                $table->text('qr_data')->nullable();
                $table->string('estado_dian', 50)->nullable();

                // Crédito
                $table->boolean('pago_parcial')->default(false)->nullable();
                $table->decimal('saldo_pendiente', 12, 2)->default(0)->nullable();

                $table->string('estado_venta', 20)->default('COMPLETADA');
                $table->text('observaciones')->nullable();
            });
        } else {
            Schema::table('venta', function (Blueprint $table) {
                if (!Schema::hasColumn('venta', 'pago_parcial')) {
                    $table->boolean('pago_parcial')->default(false)->nullable();
                } else {
                    $table->boolean('pago_parcial')->default(false)->nullable()->change();
                }

                if (!Schema::hasColumn('venta', 'saldo_pendiente')) {
                    $table->decimal('saldo_pendiente', 12, 2)->default(0)->nullable();
                } else {
                    $table->decimal('saldo_pendiente', 12, 2)->default(0)->nullable()->change();
                }
            });
        }

        if (!Schema::hasTable('venta_detalle')) {
            Schema::create('venta_detalle', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venta_id')->nullable()->constrained('venta');
                $table->foreignId('producto_id')->nullable()->constrained('producto');
                $table->foreignId('producto_presentacion_id')->nullable()->constrained('producto_presentacion');
                $table->foreignId('lote_id')->nullable()->constrained('lote');
                $table->decimal('cantidad', 14, 4);
                $table->decimal('precio_unitario', 14, 2);
                $table->foreignId('regla_descuento_id')->nullable()->constrained('regla_descuento');
                $table->decimal('monto_descuento', 14, 2)->default(0);
                $table->decimal('impuesto_valor', 14, 2)->nullable();
                $table->decimal('subtotal_linea', 14, 2)->nullable();
            });
        }

        if (!Schema::hasTable('venta_detalle_serial')) {
            Schema::create('venta_detalle_serial', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('venta_detalle_id')->nullable()->constrained('venta_detalle');
                $table->foreignId('serial_producto_id')->nullable()->constrained('serial_producto');
            });
        }

        if (!Schema::hasTable('venta_pago')) {
            Schema::create('venta_pago', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venta_id')->nullable()->constrained('venta');
                $table->string('metodo_pago', 50)->nullable(); // EFECTIVO, TARJETA, NEQUI
                $table->decimal('monto', 14, 2)->nullable();
                $table->string('referencia', 100)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_pago');
        Schema::dropIfExists('venta_detalle_serial');
        Schema::dropIfExists('venta_detalle');
        Schema::dropIfExists('venta');
    }
};
