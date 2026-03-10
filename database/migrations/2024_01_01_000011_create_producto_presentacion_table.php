<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('producto_presentacion')) {
            Schema::create('producto_presentacion', function (Blueprint $table) {
                $table->id();
                $table->foreignId('producto_id')->nullable()->constrained('producto');
                $table->string('nombre', 100)->nullable();
                $table->string('codigo_barras', 150)->nullable()->unique();
                $table->decimal('factor_conversion', 14, 4)->default(1);
                $table->boolean('es_default_compra')->default(false);
                $table->boolean('es_default_venta')->default(false);
                $table->decimal('precio', 18, 2)->default(0);
                $table->decimal('costo', 18, 2)->default(0);
                $table->boolean('activo')->default(true);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_presentacion');
    }
};
