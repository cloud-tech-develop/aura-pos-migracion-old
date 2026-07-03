<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V73: Nómina/Asistencia Fase 6 — auditoría transversal. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('auditoria_nomina_asistencia')) {
            return;
        }

        Schema::create('auditoria_nomina_asistencia', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->string('entidad', 50);   // NOMINA | ASISTENCIA_DIA | INCIDENCIA | ...
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->string('accion', 50);     // LIQUIDAR | APROBAR | PAGAR | ...
            $table->integer('usuario_id')->nullable();
            $table->timestamp('fecha_hora')->useCurrent();
            $table->text('valor_anterior')->nullable();
            $table->text('valor_nuevo')->nullable();
            $table->string('motivo', 255)->nullable();
            $table->string('ip', 60)->nullable();
            $table->string('origen', 30)->nullable();

            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->index('empresa_id', 'idx_auditoria_na_empresa');
            $table->index(['entidad', 'entidad_id'], 'idx_auditoria_na_entidad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_nomina_asistencia');
    }
};
