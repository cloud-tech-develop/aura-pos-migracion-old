<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('regla_descuento')) {
            Schema::create('regla_descuento', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->nullable()->constrained('empresa');
                $table->string('nombre', 150)->nullable();
                $table->timestamp('fecha_inicio')->nullable();
                $table->timestamp('fecha_fin')->nullable();
                $table->jsonb('dias_semana')->nullable(); // [1,2,3]
                $table->time('hora_inicio')->nullable();
                $table->time('hora_fin')->nullable();
                $table->foreignId('categoria_id')->nullable()->constrained('categoria');
                $table->foreignId('producto_id')->nullable()->constrained('producto');
                $table->string('tipo_descuento', 20)->nullable(); // PORCENTAJE, MONTO
                $table->decimal('valor', 14, 2)->nullable();
                $table->boolean('activo')->default(true);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('regla_descuento');
    }
};
