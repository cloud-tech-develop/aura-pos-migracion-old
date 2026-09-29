<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V78: Captura de asistencia por frente (Fase B). Cabecera por frente-fecha +
 * detalle por trabajador + alertas antifraude. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('asistencia_frente')) {
            Schema::create('asistencia_frente', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('proyecto_id');
                $table->unsignedBigInteger('frente_id');
                $table->unsignedBigInteger('plantilla_id')->nullable();
                $table->unsignedBigInteger('soporte_pdf_id')->nullable();
                $table->unsignedBigInteger('lider_id')->nullable();
                $table->date('fecha');
                $table->string('estado', 30)->default('BORRADOR');
                $table->string('observacion_lider', 500)->nullable();
                $table->string('observacion_admin', 500)->nullable();
                $table->timestamp('enviado_revision_at')->nullable();
                $table->unsignedBigInteger('aprobado_por')->nullable();
                $table->timestamp('aprobado_at')->nullable();
                $table->unsignedBigInteger('rechazado_por')->nullable();
                $table->timestamp('rechazado_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->timestamp('deleted_at')->nullable();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->foreign('proyecto_id')->references('id')->on('proyecto');
                $table->foreign('frente_id')->references('id')->on('proyecto_frente');
                $table->index('empresa_id', 'idx_asis_frente_empresa');
                $table->index(['frente_id', 'fecha'], 'idx_asis_frente_frente');
            });

            DB::statement("ALTER TABLE asistencia_frente ADD CONSTRAINT chk_asis_frente_estado CHECK (estado IN ('BORRADOR', 'ENVIADO_REVISION', 'EN_CORRECCION', 'APROBADO', 'RECHAZADO', 'ENVIADO_NOMINA', 'ANULADO'))");
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS ux_asis_frente_fecha ON asistencia_frente(frente_id, fecha) WHERE deleted_at IS NULL');
        }

        if (!Schema::hasTable('asistencia_frente_detalle')) {
            Schema::create('asistencia_frente_detalle', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('asistencia_frente_id');
                $table->unsignedBigInteger('proyecto_id');
                $table->unsignedBigInteger('frente_id');
                $table->unsignedBigInteger('empleado_id');
                $table->date('fecha');
                $table->time('hora_entrada')->nullable();
                $table->time('hora_salida')->nullable();
                $table->decimal('horas_trabajadas', 6, 2)->default(0);
                $table->decimal('horas_ordinarias', 6, 2)->default(0);
                $table->decimal('horas_extra_diurnas', 6, 2)->default(0);
                $table->decimal('horas_extra_nocturnas', 6, 2)->default(0);
                $table->decimal('horas_dominicales', 6, 2)->default(0);
                $table->decimal('horas_festivas', 6, 2)->default(0);
                $table->string('estado_asistencia', 20)->default('SIN_REGISTRO');
                $table->string('estado_revision', 20)->default('PENDIENTE');
                $table->string('observacion_lider', 255)->nullable();
                $table->string('observacion_admin', 255)->nullable();
                $table->unsignedBigInteger('aprobado_por')->nullable();
                $table->timestamp('aprobado_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->timestamp('deleted_at')->nullable();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->foreign('asistencia_frente_id')->references('id')->on('asistencia_frente')->onDelete('cascade');
                $table->foreign('empleado_id')->references('id')->on('empleados');
                $table->index('asistencia_frente_id', 'idx_asis_det_frente');
                $table->index(['empleado_id', 'fecha'], 'idx_asis_det_empleado');
            });

            DB::statement("ALTER TABLE asistencia_frente_detalle ADD CONSTRAINT chk_asis_det_asistencia CHECK (estado_asistencia IN ('ASISTIO', 'NO_ASISTIO', 'LLEGO_TARDE', 'SALIO_TEMPRANO', 'PERMISO', 'INCAPACIDAD', 'VACACIONES', 'SUSPENDIDO', 'SIN_REGISTRO'))");
            DB::statement("ALTER TABLE asistencia_frente_detalle ADD CONSTRAINT chk_asis_det_revision CHECK (estado_revision IN ('PENDIENTE', 'APROBADO', 'RECHAZADO', 'AJUSTADO', 'ENVIADO_NOMINA'))");
        }

        if (!Schema::hasTable('asistencia_alerta')) {
            Schema::create('asistencia_alerta', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('asistencia_frente_id')->nullable();
                $table->unsignedBigInteger('asistencia_frente_detalle_id')->nullable();
                $table->unsignedBigInteger('proyecto_id')->nullable();
                $table->unsignedBigInteger('frente_id')->nullable();
                $table->unsignedBigInteger('empleado_id')->nullable();
                $table->string('tipo_alerta', 40);
                $table->string('nivel', 15)->default('ADVERTENCIA');
                $table->string('descripcion', 255)->nullable();
                $table->string('estado', 15)->default('ABIERTA');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->timestamp('deleted_at')->nullable();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->foreign('asistencia_frente_id')->references('id')->on('asistencia_frente')->onDelete('cascade');
                $table->index('asistencia_frente_id', 'idx_asis_alerta_frente');
            });

            DB::statement("ALTER TABLE asistencia_alerta ADD CONSTRAINT chk_asis_alerta_nivel CHECK (nivel IN ('INFO', 'ADVERTENCIA', 'CRITICA'))");
            DB::statement("ALTER TABLE asistencia_alerta ADD CONSTRAINT chk_asis_alerta_estado CHECK (estado IN ('ABIERTA', 'REVISADA', 'RESUELTA', 'IGNORADA'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia_alerta');
        Schema::dropIfExists('asistencia_frente_detalle');
        Schema::dropIfExists('asistencia_frente');
    }
};
