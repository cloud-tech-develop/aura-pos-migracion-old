<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobante_caja', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->string('numero_comprobante', 20)->unique();
            $table->string('tipo', 10);          // INGRESO | EGRESO
            $table->string('concepto', 500);
            $table->decimal('monto', 15, 2);
            $table->string('metodo_pago', 30)->nullable();  // EFECTIVO | TRANSFERENCIA | null
            $table->string('entregado_a', 200)->nullable();
            $table->string('origen', 30)->nullable();       // MANUAL | DEVOLUCION | ABONO_CXC | ABONO_CXP
            $table->unsignedBigInteger('origen_id')->nullable();
            $table->unsignedBigInteger('turno_caja_id')->nullable();
            $table->unsignedInteger('usuario_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->foreign('turno_caja_id')->references('id')->on('turno_caja');
            $table->foreign('usuario_id')->references('id')->on('usuario');

            $table->index('empresa_id', 'idx_comprobante_caja_empresa');
            $table->index(['empresa_id', 'tipo'], 'idx_comprobante_caja_tipo');
            $table->index('turno_caja_id', 'idx_comprobante_caja_turno');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobante_caja');
    }
};