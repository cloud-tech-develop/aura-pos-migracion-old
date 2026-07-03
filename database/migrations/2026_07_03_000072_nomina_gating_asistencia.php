<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V72: Nómina Fase 5 — gating de asistencia y autorización excepcional. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Amplía los tipos de novedad permitidos (drop-if-exists + add → idempotente).
        DB::statement("ALTER TABLE nomina_novedad DROP CONSTRAINT IF EXISTS chk_novedad_tipo");
        DB::statement("ALTER TABLE nomina_novedad ADD CONSTRAINT chk_novedad_tipo CHECK (tipo IN ("
            . "'HORA_EXTRA_DIURNA', 'HORA_EXTRA_NOCTURNA', 'HORA_EXTRA_DOMINICAL', "
            . "'HORA_EXTRA_FESTIVO', 'HORA_EXTRA_DOMINICAL_FESTIVA', "
            . "'RECARGO_NOCTURNO', 'RECARGO_DOMINICAL_FESTIVO', "
            . "'AUSENCIA_NO_JUSTIFICADA', 'LLEGADA_TARDE_DESCONTADA', 'SALIDA_TEMPRANA_DESCONTADA', "
            . "'PERMISO_REMUNERADO', 'PERMISO_NO_REMUNERADO', "
            . "'INCAPACIDAD', 'LICENCIA_REMUNERADA', 'LICENCIA_NO_REMUNERADA', 'VACACIONES', "
            . "'BONO', 'COMISION', 'PRESTAMO', 'EMBARGO', 'OTRO_DEVENGO', 'OTRO_DESCUENTO'"
            . "))");

        if (!Schema::hasTable('autorizacion_liquidacion_excepcional')) {
            Schema::create('autorizacion_liquidacion_excepcional', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('empleado_id');
                $table->unsignedBigInteger('periodo_nomina_id');
                $table->integer('usuario_autoriza')->nullable();
                $table->timestamp('fecha_autorizacion')->useCurrent();
                $table->string('motivo', 40);
                $table->string('observacion', 255)->nullable();
                $table->string('estado', 20)->default('ACTIVA'); // ACTIVA | ANULADA

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->foreign('empleado_id')->references('id')->on('empleados')->onDelete('cascade');
                $table->foreign('periodo_nomina_id')->references('id')->on('periodo_nomina');

                $table->index('empresa_id', 'idx_autoriz_liq_empresa');
                $table->index(['empleado_id', 'periodo_nomina_id'], 'idx_autoriz_liq_empleado');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('autorizacion_liquidacion_excepcional');
    }
};
