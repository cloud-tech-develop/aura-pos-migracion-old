<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tesoreria_movimiento', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedBigInteger('cuenta_bancaria_id');
            $table->string('tipo', 30); // EGRESO | RECAUDO | TRANSFERENCIA_SALIDA | TRANSFERENCIA_ENTRADA
            $table->decimal('monto', 15, 2);
            $table->string('concepto', 500);
            $table->string('beneficiario', 300)->nullable();
            $table->string('referencia', 200)->nullable();
            $table->date('fecha');
            $table->string('categoria', 100)->nullable();
                // Egresos:  PAGO_PROVEEDOR | GASTO_OPERATIVO | NOMINA | IMPUESTO | SERVICIO | OTROS
                // Recaudos: VENTA | COBRO_CARTERA | PRESTAMO | DEVOLUCION | OTROS
            $table->unsignedBigInteger('transferencia_id')->nullable(); // liga SALIDA con ENTRADA
            $table->boolean('conciliado')->default(false);
            $table->date('fecha_conciliacion')->nullable();
            $table->boolean('anulado')->default(false);
            $table->unsignedInteger('usuario_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->foreign('cuenta_bancaria_id')->references('id')->on('cuenta_bancaria');
            $table->foreign('usuario_id')->references('id')->on('usuario');

            $table->index('empresa_id', 'idx_tesm_empresa');
            $table->index('cuenta_bancaria_id', 'idx_tesm_cuenta');
            $table->index('fecha', 'idx_tesm_fecha');
            $table->index('tipo', 'idx_tesm_tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tesoreria_movimiento');
    }
};