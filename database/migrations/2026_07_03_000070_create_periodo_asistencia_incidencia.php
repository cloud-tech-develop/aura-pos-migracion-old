<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V70: Asistencia Fase 3 — período de asistencia e incidencias. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('periodo_asistencia')) {
            Schema::create('periodo_asistencia', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('periodo_nomina_id')->nullable();
                $table->date('fecha_inicio');
                $table->date('fecha_fin');
                $table->string('estado', 30)->default('ABIERTO');
                $table->integer('creado_por')->nullable();
                $table->timestamp('fecha_creacion')->useCurrent();
                $table->integer('cerrado_por')->nullable();
                $table->timestamp('fecha_cierre')->nullable();
                $table->integer('aprobado_por')->nullable();
                $table->timestamp('fecha_aprobacion')->nullable();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->foreign('periodo_nomina_id')->references('id')->on('periodo_nomina');
                $table->index('empresa_id', 'idx_periodo_asistencia_empresa');
            });

            DB::statement("ALTER TABLE periodo_asistencia ADD CONSTRAINT chk_periodo_asistencia_estado CHECK (estado IN ('ABIERTO', 'EN_REVISION', 'APROBADO', 'BLOQUEADO', 'ENVIADO_A_NOMINA', 'ANULADO'))");
        }

        if (!Schema::hasTable('asistencia_incidencia')) {
            Schema::create('asistencia_incidencia', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('asistencia_dia_id')->nullable();
                $table->unsignedBigInteger('empleado_id');
                $table->date('fecha');
                $table->string('tipo_incidencia', 40);
                $table->string('descripcion', 255)->nullable();
                $table->string('estado', 30)->default('PENDIENTE_REVISION');
                $table->boolean('requiere_soporte')->default(false);
                $table->string('soporte_url', 255)->nullable();
                $table->integer('registrado_por')->nullable();
                $table->timestamp('fecha_registro')->useCurrent();
                $table->integer('revisado_por')->nullable();
                $table->timestamp('fecha_revision')->nullable();
                $table->string('observacion_revision', 255)->nullable();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->foreign('asistencia_dia_id')->references('id')->on('asistencia_dia')->onDelete('cascade');
                $table->foreign('empleado_id')->references('id')->on('empleados')->onDelete('cascade');

                $table->index('empresa_id', 'idx_incidencia_empresa');
                $table->index(['empleado_id', 'fecha'], 'idx_incidencia_empleado_fec');
                $table->index('asistencia_dia_id', 'idx_incidencia_dia');
            });

            DB::statement("ALTER TABLE asistencia_incidencia ADD CONSTRAINT chk_incidencia_estado CHECK (estado IN ('PENDIENTE_REVISION', 'JUSTIFICADA', 'NO_JUSTIFICADA', 'APROBADA_COMO_NOVEDAD', 'RECHAZADA', 'CORREGIDA', 'ANULADA'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia_incidencia');
        Schema::dropIfExists('periodo_asistencia');
    }
};
