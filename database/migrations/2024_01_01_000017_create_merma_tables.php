<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('motivo_merma')) {
            Schema::create('motivo_merma', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->nullable()->constrained('empresa');
                $table->string('nombre', 100)->nullable(); // Vencimiento, Robo
                $table->boolean('afecta_contabilidad')->default(true);
            });
        }

        if (!Schema::hasTable('merma')) {
            Schema::create('merma', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->nullable()->constrained('empresa');
                $table->foreignId('sucursal_id')->nullable()->constrained('sucursal');
                $table->foreignId('usuario_id')->nullable()->constrained('usuario');
                $table->foreignId('motivo_id')->nullable()->constrained('motivo_merma');
                $table->timestamp('fecha')->useCurrent();
                $table->text('observacion')->nullable();
                $table->decimal('costo_total', 14, 2)->nullable();
                $table->string('estado', 20)->default('APROBADA');
            });
        }

        if (!Schema::hasTable('merma_detalle')) {
            Schema::create('merma_detalle', function (Blueprint $table) {
                $table->id();
                $table->foreignId('merma_id')->nullable()->constrained('merma');
                $table->foreignId('producto_id')->nullable()->constrained('producto');
                $table->foreignId('lote_id')->nullable()->constrained('lote');
                $table->decimal('cantidad', 14, 4)->nullable();
                $table->decimal('costo_unitario', 14, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('merma_detalle');
        Schema::dropIfExists('merma');
        Schema::dropIfExists('motivo_merma');
    }
};
