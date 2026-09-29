<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V71: Asistencia Fase 4 — trazabilidad en novedades y staging de novedades desde asistencia.
 * Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nomina_novedad', function (Blueprint $table) {
            if (!Schema::hasColumn('nomina_novedad', 'naturaleza'))          $table->string('naturaleza', 20)->default('DEVENGADO');
            if (!Schema::hasColumn('nomina_novedad', 'origen'))              $table->string('origen', 20)->default('MANUAL');
            if (!Schema::hasColumn('nomina_novedad', 'estado'))              $table->string('estado', 20)->default('APLICADA');
            if (!Schema::hasColumn('nomina_novedad', 'requiere_aprobacion')) $table->boolean('requiere_aprobacion')->default(false);
        });

        if (!Schema::hasTable('asistencia_novedad_nomina')) {
            Schema::create('asistencia_novedad_nomina', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('periodo_nomina_id')->nullable();
                $table->unsignedBigInteger('asistencia_dia_id')->nullable();
                $table->unsignedBigInteger('asistencia_incidencia_id')->nullable();
                $table->unsignedBigInteger('empleado_id');
                $table->string('tipo_novedad', 40);
                $table->string('unidad', 10)->default('HORAS'); // HORAS | DIAS | MINUTOS | VALOR
                $table->decimal('cantidad', 10, 2)->default(0);
                $table->decimal('valor_manual', 15, 2)->nullable();
                $table->string('origen', 20)->default('ASISTENCIA');
                $table->string('estado', 20)->default('PENDIENTE');
                $table->timestamp('fecha_generacion')->useCurrent();
                $table->integer('generado_por')->nullable();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->foreign('periodo_nomina_id')->references('id')->on('periodo_nomina');
                $table->foreign('asistencia_dia_id')->references('id')->on('asistencia_dia')->onDelete('set null');
                $table->foreign('asistencia_incidencia_id')->references('id')->on('asistencia_incidencia')->onDelete('set null');
                $table->foreign('empleado_id')->references('id')->on('empleados')->onDelete('cascade');

                $table->index('empresa_id', 'idx_asis_nov_empresa');
                $table->index('periodo_nomina_id', 'idx_asis_nov_periodo');
                $table->index('empleado_id', 'idx_asis_nov_empleado');
            });

            DB::statement("ALTER TABLE asistencia_novedad_nomina ADD CONSTRAINT chk_asis_nov_unidad CHECK (unidad IN ('HORAS', 'DIAS', 'MINUTOS', 'VALOR'))");
            DB::statement("ALTER TABLE asistencia_novedad_nomina ADD CONSTRAINT chk_asis_nov_estado CHECK (estado IN ('PENDIENTE', 'APROBADA', 'RECHAZADA', 'ENVIADA_A_NOMINA'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia_novedad_nomina');
        Schema::table('nomina_novedad', function (Blueprint $table) {
            $table->dropColumn(['naturaleza', 'origen', 'estado', 'requiere_aprobacion']);
        });
    }
};
