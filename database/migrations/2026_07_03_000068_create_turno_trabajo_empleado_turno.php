<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V68: Asistencia Fase 1 — turnos de trabajo y asignación. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('turno_trabajo')) {
            Schema::create('turno_trabajo', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->string('nombre', 80);
                $table->time('hora_inicio');
                $table->time('hora_fin');
                $table->integer('minutos_descanso')->default(0);
                $table->integer('tolera_llegada_tarde_min')->default(0);
                $table->boolean('cruza_medianoche')->default(false);
                $table->boolean('activo')->default(true);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->index('empresa_id', 'idx_turno_trabajo_empresa');
            });
        }

        if (!Schema::hasTable('empleado_turno')) {
            Schema::create('empleado_turno', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('empleado_id');
                $table->unsignedBigInteger('turno_id');
                $table->date('fecha_inicio');
                $table->date('fecha_fin')->nullable();
                $table->string('dias_semana', 30)->default('1,2,3,4,5'); // ISO 1=Lun..7=Dom (CSV)
                $table->boolean('activo')->default(true);
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->foreign('empleado_id')->references('id')->on('empleados')->onDelete('cascade');
                $table->foreign('turno_id')->references('id')->on('turno_trabajo');

                $table->index('empresa_id', 'idx_empleado_turno_empresa');
                $table->index('empleado_id', 'idx_empleado_turno_empleado');
                $table->index(['empleado_id', 'fecha_inicio', 'fecha_fin'], 'idx_empleado_turno_vigencia');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_turno');
        Schema::dropIfExists('turno_trabajo');
    }
};
