<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresa');
            $table->foreignId('tercero_id')->nullable()->constrained('tercero');
            $table->unsignedInteger('turno_caja_id')->nullable();
            $table->string('numero', 20)->nullable();
            $table->date('fecha')->default(now());
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('subtotal', 14, 2)->nullable();
            $table->decimal('iva', 14, 2)->nullable();
            $table->decimal('descuento', 14, 2)->nullable();
            $table->decimal('total', 14, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado', 20)->default('PENDIENTE');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('cotizacion_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizacion')->onDelete('cascade');
            $table->foreignId('producto_id')->nullable()->constrained('producto');
            $table->string('descripcion', 255)->nullable();
            $table->decimal('cantidad', 10, 3)->nullable();
            $table->decimal('precio_unitario', 14, 2)->nullable();
            $table->decimal('iva_porcentaje', 5, 2)->nullable();
            $table->decimal('descuento_valor', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_detalle');
        Schema::dropIfExists('cotizacion');
    }
};