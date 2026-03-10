<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lista_precios')) {
            Schema::create('lista_precios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->nullable()->constrained('empresa');
                $table->string('nombre', 100)->nullable();
                $table->boolean('activa')->default(true);
            });
        }

        if (!Schema::hasTable('producto_precio')) {
            Schema::create('producto_precio', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lista_precio_id')->nullable()->constrained('lista_precios');
                $table->foreignId('producto_presentacion_id')->nullable()->constrained('producto_presentacion');
                $table->decimal('precio', 14, 2);
                $table->decimal('utilidad_esperada', 5, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_precio');
        Schema::dropIfExists('lista_precios');
    }
};
