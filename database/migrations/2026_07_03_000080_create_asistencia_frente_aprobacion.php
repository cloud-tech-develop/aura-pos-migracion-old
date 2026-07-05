<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V80: Traza de revisión/aprobación de asistencia (Fase D). Cada aprobación,
 * rechazo, solicitud de corrección o ajuste queda registrado. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asistencia_frente_aprobacion')) {
            return;
        }

        Schema::create('asistencia_frente_aprobacion', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedBigInteger('asistencia_frente_id');
            $table->unsignedBigInteger('asistencia_frente_detalle_id')->nullable();
            $table->unsignedBigInteger('administrador_id')->nullable();
            $table->string('accion', 30);
            $table->string('valor_anterior', 255)->nullable();
            $table->string('valor_aprobado', 255)->nullable();
            $table->string('observacion', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->timestamp('deleted_at')->nullable();

            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->foreign('asistencia_frente_id')->references('id')->on('asistencia_frente')->onDelete('cascade');
            $table->index('asistencia_frente_id', 'idx_asis_aprob_frente');
        });

        DB::statement("ALTER TABLE asistencia_frente_aprobacion ADD CONSTRAINT chk_asis_aprob_accion CHECK (accion IN ('APROBAR', 'RECHAZAR', 'SOLICITAR_CORRECCION', 'AJUSTAR', 'ANULAR'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia_frente_aprobacion');
    }
};
