<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('producto')) {
            Schema::create('producto', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->nullable()->constrained('empresa');
                $table->foreignId('categoria_id')->nullable()->constrained('categoria');
                $table->foreignId('marca_id')->nullable()->constrained('marca');
                $table->foreignId('unidad_medida_base_id')->nullable()->constrained('unidad_medida');

                $table->string('sku', 100)->nullable();
                $table->string('codigo_barras', 150)->nullable();
                $table->string('nombre', 255);
                $table->text('descripcion')->nullable();
                $table->text('imagen_url')->nullable();

                $table->string('tipo_producto', 20)->default('ESTANDAR');
                $table->boolean('maneja_inventario')->default(true);
                $table->boolean('maneja_lotes')->default(false);
                $table->boolean('maneja_serial')->default(false);
                $table->boolean('visible_en_pos')->default(true);

                $table->decimal('costo', 19, 2)->default(0);
                $table->decimal('precio', 19, 2)->default(0);
                $table->decimal('iva_porcentaje', 5, 2)->default(0);
                $table->decimal('impoconsumo', 14, 2)->default(0);

                $table->jsonb('atributos')->default('{}');

                $table->boolean('activo')->default(true);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->timestamp('deleted_at')->nullable();

                $table->unique(['empresa_id', 'codigo_barras'], 'uq_producto_codigo');
            });

            // Índices para búsqueda rápida
            Schema::table('producto', function (Blueprint $table) {
                $table->index(['empresa_id', 'nombre'], 'idx_producto_nombre');
                $table->index(['empresa_id', 'sku'], 'idx_producto_sku');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('producto');
    }
};
