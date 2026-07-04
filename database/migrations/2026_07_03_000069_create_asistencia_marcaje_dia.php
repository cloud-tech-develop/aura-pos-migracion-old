<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V69: Asistencia Fase 2 — marcajes y consolidación diaria. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('asistencia_marcaje')) {
            Schema::create('asistencia_marcaje', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('empleado_id');
                $table->date('fecha');
                $table->timestamp('fecha_hora_marcaje');
                $table->string('tipo_marcaje', 20);   // ENTRADA | SALIDA | INICIO_DESCANSO | FIN_DESCANSO
                $table->string('origen_marcaje', 20)->default('ASISTENTE');
                $table->integer('registrado_por')->nullable();
                $table->string('observacion', 255)->nullable();
                $table->string('evidencia_url', 255)->nullable();
                $table->string('estado', 20)->default('VALIDO'); // VALIDO | PENDIENTE_REVISION | ANULADO | CORREGIDO
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->foreign('empleado_id')->references('id')->on('empleados')->onDelete('cascade');

                $table->index('empresa_id', 'idx_marcaje_empresa');
                $table->index(['empleado_id', 'fecha'], 'idx_marcaje_empleado_dia');
            });

            DB::statement("ALTER TABLE asistencia_marcaje ADD CONSTRAINT chk_marcaje_tipo CHECK (tipo_marcaje IN ('ENTRADA', 'SALIDA', 'INICIO_DESCANSO', 'FIN_DESCANSO'))");
            DB::statement("ALTER TABLE asistencia_marcaje ADD CONSTRAINT chk_marcaje_estado CHECK (estado IN ('VALIDO', 'PENDIENTE_REVISION', 'ANULADO', 'CORREGIDO'))");
        }

        if (!Schema::hasTable('asistencia_dia')) {
            Schema::create('asistencia_dia', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('empleado_id');
                $table->date('fecha');
                $table->unsignedBigInteger('turno_id')->nullable();
                $table->time('hora_entrada_programada')->nullable();
                $table->time('hora_salida_programada')->nullable();
                $table->time('hora_entrada_real')->nullable();
                $table->time('hora_salida_real')->nullable();
                $table->integer('minutos_programados')->default(0);
                $table->integer('minutos_trabajados')->default(0);
                $table->integer('minutos_tarde')->default(0);
                $table->integer('minutos_salida_temprana')->default(0);
                $table->integer('minutos_extra_diurna')->default(0);
                $table->integer('minutos_extra_nocturna')->default(0);
                $table->integer('minutos_dominical_festiva')->default(0);
                $table->integer('minutos_nocturnos')->default(0);
                $table->string('estado_asistencia', 30)->default('SIN_MARCAJE_COMPLETO');
                $table->string('estado_aprobacion', 30)->default('PENDIENTE');
                $table->integer('aprobado_por')->nullable();
                $table->timestamp('fecha_aprobacion')->nullable();
                $table->string('observacion', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->foreign('empleado_id')->references('id')->on('empleados')->onDelete('cascade');
                $table->foreign('turno_id')->references('id')->on('turno_trabajo');

                $table->unique(['empleado_id', 'fecha'], 'uq_asistencia_dia_empleado_fecha');
                $table->index('empresa_id', 'idx_asistencia_dia_empresa');
                $table->index(['empleado_id', 'fecha'], 'idx_asistencia_dia_empleado_fec');
            });

            DB::statement("ALTER TABLE asistencia_dia ADD CONSTRAINT chk_asistencia_dia_aprob CHECK (estado_aprobacion IN ('PENDIENTE', 'APROBADO', 'RECHAZADO', 'AJUSTADO', 'BLOQUEADO', 'ENVIADO_A_NOMINA'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia_dia');
        Schema::dropIfExists('asistencia_marcaje');
    }
};
