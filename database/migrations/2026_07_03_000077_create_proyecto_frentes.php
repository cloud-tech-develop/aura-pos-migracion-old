<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V77: Módulo Proyectos y Frentes (Fase A). Convive con la asistencia por turnos.
 * Borrado lógico (deleted_at) y auditoría (created_by/...). Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('proyecto')) {
            Schema::create('proyecto', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->string('codigo', 30);
                $table->string('nombre', 150);
                $table->unsignedBigInteger('cliente_id')->nullable();
                $table->text('descripcion')->nullable();
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_fin')->nullable();
                $table->string('estado', 20)->default('ACTIVO');
                $table->unsignedBigInteger('centro_costo_id')->nullable();
                $table->unsignedBigInteger('responsable_administrativo_id')->nullable();
                $table->boolean('requiere_control_asistencia')->default(true);
                $table->string('ciudad', 100)->nullable();
                $table->string('ubicacion', 200)->nullable();
                $table->text('observacion')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->timestamp('deleted_at')->nullable();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->index('empresa_id', 'idx_proyecto_empresa');
            });

            DB::statement("ALTER TABLE proyecto ADD CONSTRAINT chk_proyecto_estado CHECK (estado IN ('ACTIVO', 'SUSPENDIDO', 'FINALIZADO', 'ANULADO'))");
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS ux_proyecto_empresa_codigo ON proyecto(empresa_id, codigo) WHERE deleted_at IS NULL');
        }

        if (!Schema::hasTable('proyecto_frente')) {
            Schema::create('proyecto_frente', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('proyecto_id');
                $table->string('codigo', 30);
                $table->string('nombre', 150);
                $table->text('descripcion')->nullable();
                $table->string('ubicacion', 200)->nullable();
                $table->unsignedBigInteger('lider_id')->nullable();
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_fin')->nullable();
                $table->string('estado', 20)->default('ACTIVO');
                $table->text('observacion')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->timestamp('deleted_at')->nullable();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->foreign('proyecto_id')->references('id')->on('proyecto');
                $table->index('empresa_id', 'idx_frente_empresa');
                $table->index('proyecto_id', 'idx_frente_proyecto');
            });

            DB::statement("ALTER TABLE proyecto_frente ADD CONSTRAINT chk_frente_estado CHECK (estado IN ('ACTIVO', 'SUSPENDIDO', 'FINALIZADO', 'ANULADO'))");
        }

        if (!Schema::hasTable('proyecto_frente_trabajador')) {
            Schema::create('proyecto_frente_trabajador', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('proyecto_id');
                $table->unsignedBigInteger('frente_id');
                $table->unsignedBigInteger('empleado_id');
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_fin')->nullable();
                $table->string('estado', 20)->default('ACTIVO');
                $table->text('observacion')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->timestamp('deleted_at')->nullable();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->foreign('proyecto_id')->references('id')->on('proyecto');
                $table->foreign('frente_id')->references('id')->on('proyecto_frente');
                $table->foreign('empleado_id')->references('id')->on('empleados');
                $table->index('empresa_id', 'idx_frente_trab_empresa');
                $table->index('frente_id', 'idx_frente_trab_frente');
                $table->index('empleado_id', 'idx_frente_trab_empleado');
            });

            DB::statement("ALTER TABLE proyecto_frente_trabajador ADD CONSTRAINT chk_frente_trab_estado CHECK (estado IN ('ACTIVO', 'RETIRADO', 'SUSPENDIDO', 'ANULADO'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('proyecto_frente_trabajador');
        Schema::dropIfExists('proyecto_frente');
        Schema::dropIfExists('proyecto');
    }
};
