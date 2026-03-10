<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('caja')) {
            Schema::create('caja', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sucursal_id')->nullable()->constrained('sucursal');
                $table->string('nombre', 100)->nullable();
                $table->boolean('activa')->default(true);
            });
        }

        if (!Schema::hasTable('turno_caja')) {
            Schema::create('turno_caja', function (Blueprint $table) {
                $table->id();
                $table->foreignId('caja_id')->nullable()->constrained('caja');
                $table->foreignId('usuario_id')->nullable()->constrained('usuario');
                $table->timestamp('fecha_apertura')->useCurrent();
                $table->timestamp('fecha_cierre')->nullable();
                $table->decimal('base_inicial', 14, 2)->nullable();
                $table->decimal('total_efectivo_sistema', 14, 2)->nullable();
                $table->decimal('total_efectivo_real', 14, 2)->nullable();
                $table->decimal('diferencia', 14, 2)->nullable();
                $table->string('estado', 20)->default('ABIERTA');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('turno_caja');
        Schema::dropIfExists('caja');
    }
};
