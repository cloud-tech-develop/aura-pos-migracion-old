<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Perfil de crédito por cliente
        Schema::create('tercero_credito', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedBigInteger('tercero_id');
            $table->decimal('cupo_credito_inicial', 15, 2)->default(0);
            $table->decimal('cupo_credito_actual', 15, 2)->default(0);
            $table->integer('plazo_dias')->default(30);
            $table->string('estado_credito', 20)->default('ACTIVO');   // ACTIVO | SUSPENDIDO | BLOQUEADO | EN_ESTUDIO
            $table->string('nivel_riesgo', 20)->default('BAJO');       // BAJO | MEDIO | ALTO | CRITICO
            $table->integer('score_crediticio')->default(500);          // 0-1000
            $table->boolean('requiere_autorizacion')->default(false);
            $table->integer('dias_mora_tolerancia')->default(30);
            $table->timestamp('fecha_ultimo_estudio')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->foreign('tercero_id')->references('id')->on('tercero');
            $table->unique(['empresa_id', 'tercero_id']);

            $table->index('empresa_id', 'idx_tercero_credito_empresa');
            $table->index('tercero_id', 'idx_tercero_credito_tercero');
        });

        // 2. Historial de eventos de crédito
        Schema::create('historial_credito', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedBigInteger('tercero_id');
            $table->string('tipo_evento', 30);
                // APERTURA | AUMENTO_CUPO | REDUCCION_CUPO | BLOQUEO |
                // DESBLOQUEO | ESTUDIO | MORA_DETECTADA | NORMALIZACION
            $table->decimal('cupo_anterior', 15, 2)->nullable();
            $table->decimal('cupo_nuevo', 15, 2)->nullable();
            $table->integer('score_anterior')->nullable();
            $table->integer('score_nuevo')->nullable();
            $table->text('motivo')->nullable();
            $table->unsignedInteger('usuario_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->foreign('tercero_id')->references('id')->on('tercero');
            $table->foreign('usuario_id')->references('id')->on('usuario');

            $table->index(['empresa_id', 'tercero_id'], 'idx_historial_credito_tercero');
        });

        // 3. Reglas empresariales del motor de crédito
        Schema::create('regla_credito', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->string('nombre', 100);
            $table->string('tipo', 30);    // BLOQUEO | ALERTA | AUMENTO_CUPO | REDUCCION_CUPO
            $table->string('evento', 30);  // AL_VENDER | AL_PAGAR | PERIODICO
            $table->jsonb('condicion_json')->default('{}');
            $table->jsonb('accion_json')->default('{}');
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(1);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('empresa_id')->references('id')->on('empresa');

            $table->index(['empresa_id', 'activo'], 'idx_regla_credito_empresa');
        });

        // 4. Log de scores calculados
        Schema::create('score_crediticio_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedBigInteger('tercero_id');
            $table->integer('score');
            $table->jsonb('factores_json')->default('{}');
            $table->timestamp('calculated_at')->useCurrent();

            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->foreign('tercero_id')->references('id')->on('tercero');

            $table->index(['empresa_id', 'tercero_id', 'calculated_at'], 'idx_score_log_tercero');
        });

        // 5. Solicitudes de autorización cuando se supera el cupo
        Schema::create('solicitud_autorizacion_credito', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedBigInteger('tercero_id');
            $table->unsignedBigInteger('venta_id')->nullable();
            $table->decimal('monto_solicitado', 15, 2);
            $table->decimal('cupo_disponible', 15, 2);
            $table->decimal('excedente', 15, 2);
            $table->string('estado', 20)->default('PENDIENTE'); // PENDIENTE | APROBADA | RECHAZADA
            $table->unsignedInteger('aprobado_por_id')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->foreign('tercero_id')->references('id')->on('tercero');
            $table->foreign('venta_id')->references('id')->on('venta');
            $table->foreign('aprobado_por_id')->references('id')->on('usuario');

            $table->index(['empresa_id', 'estado'], 'idx_solicitud_credito_empresa');
        });

        // 6. Gestión de cobros y notas sobre cuentas vencidas
        Schema::create('gestion_cobro', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedBigInteger('tercero_id');
            $table->unsignedBigInteger('cuenta_cobrar_id')->nullable();
            $table->string('tipo_gestion', 30); // LLAMADA | EMAIL | VISITA | NOTA | ACUERDO_PAGO | MENSAJE
            $table->string('resultado', 30)->nullable(); // CONTACTADO | NO_CONTESTO | PROMESA_PAGO | RENUENTE | PAGADO
            $table->text('nota')->nullable();
            $table->date('fecha_promesa_pago')->nullable();
            $table->decimal('monto_prometido', 15, 2)->nullable();
            $table->unsignedInteger('usuario_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->foreign('tercero_id')->references('id')->on('tercero');
            $table->foreign('cuenta_cobrar_id')->references('id')->on('cuentas_cobrar');
            $table->foreign('usuario_id')->references('id')->on('usuario');

            $table->index(['empresa_id', 'tercero_id'], 'idx_gestion_cobro_tercero');
            $table->index('cuenta_cobrar_id', 'idx_gestion_cobro_cuenta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestion_cobro');
        Schema::dropIfExists('solicitud_autorizacion_credito');
        Schema::dropIfExists('score_crediticio_log');
        Schema::dropIfExists('regla_credito');
        Schema::dropIfExists('historial_credito');
        Schema::dropIfExists('tercero_credito');
    }
};