<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lote')) {
            Schema::create('lote', function (Blueprint $table) {
                $table->id();
                $table->foreignId('producto_id')->nullable()->constrained('producto');
                $table->foreignId('sucursal_id')->nullable()->constrained('sucursal');
                $table->string('codigo_lote', 100)->nullable();
                $table->date('fecha_vencimiento')->nullable();
                $table->decimal('stock_actual', 14, 4)->nullable();
                $table->decimal('costo_unitario', 14, 2)->nullable();
                $table->boolean('activo')->default(true);
            });
        }

        if (!Schema::hasTable('serial_producto')) {
            Schema::create('serial_producto', function (Blueprint $table) {
                $table->id();
                $table->foreignId('producto_id')->nullable()->constrained('producto');
                $table->foreignId('sucursal_id')->nullable()->constrained('sucursal');
                $table->string('serial', 200)->unique();
                $table->string('estado', 20)->default('DISPONIBLE'); // DISPONIBLE, VENDIDO, GARANTIA
            });
        }

        if (!Schema::hasTable('inventario')) {
            Schema::create('inventario', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sucursal_id')->nullable()->constrained('sucursal');
                $table->foreignId('producto_id')->nullable()->constrained('producto');
                $table->decimal('stock_actual', 14, 4)->default(0);
                $table->decimal('stock_minimo', 14, 4)->default(0);
                $table->string('ubicacion', 50)->nullable();
                $table->timestamp('updated_at')->useCurrent();

                $table->unique(['sucursal_id', 'producto_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario');
        Schema::dropIfExists('serial_producto');
        Schema::dropIfExists('lote');
    }
};
