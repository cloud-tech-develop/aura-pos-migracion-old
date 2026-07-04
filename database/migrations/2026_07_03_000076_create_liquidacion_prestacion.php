<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V76: Liquidación de prestaciones sociales (prima, vacaciones, cesantías,
 * intereses, liquidación definitiva, indemnización). Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('liquidacion_prestacion')) {
            return;
        }

        Schema::create('liquidacion_prestacion', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedBigInteger('empleado_id');
            $table->string('tipo', 30);   // PRIMA | VACACIONES | CESANTIAS | INTERESES_CESANTIAS | LIQUIDACION_DEFINITIVA | INDEMNIZACION
            $table->date('fecha_desde');
            $table->date('fecha_hasta');
            $table->integer('dias')->default(0);
            $table->decimal('base_salarial', 15, 2)->default(0);
            $table->decimal('valor', 15, 2)->default(0);
            $table->string('estado', 20)->default('BORRADOR'); // BORRADOR | APROBADA | PAGADA | ANULADA
            $table->string('medio_pago', 20)->nullable();      // EFECTIVO | TRANSFERENCIA
            $table->unsignedBigInteger('cuenta_bancaria_id')->nullable();
            $table->timestamp('fecha_pago')->nullable();
            $table->string('observacion', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->foreign('empleado_id')->references('id')->on('empleados');
            $table->foreign('cuenta_bancaria_id')->references('id')->on('cuenta_bancaria');

            $table->index('empresa_id', 'idx_prestacion_empresa');
            $table->index('empleado_id', 'idx_prestacion_empleado');
        });

        DB::statement("ALTER TABLE liquidacion_prestacion ADD CONSTRAINT chk_prestacion_tipo CHECK (tipo IN ('PRIMA', 'VACACIONES', 'CESANTIAS', 'INTERESES_CESANTIAS', 'LIQUIDACION_DEFINITIVA', 'INDEMNIZACION'))");
        DB::statement("ALTER TABLE liquidacion_prestacion ADD CONSTRAINT chk_prestacion_estado CHECK (estado IN ('BORRADOR', 'APROBADA', 'PAGADA', 'ANULADA'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidacion_prestacion');
    }
};
