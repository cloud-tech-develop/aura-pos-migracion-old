<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V84: Membresías / suscripciones de clientes (platform). Cada empresa es un
 * cliente de pago ÚNICO (activa indefinida) o MENSUAL (con próximo pago).
 * El estado VENCIDA se calcula; el impago solo alerta. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('empresa_suscripcion')) {
            Schema::create('empresa_suscripcion', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->string('tipo_plan', 20)->default('MENSUAL');
                $table->string('estado', 20)->default('ACTIVA');
                $table->decimal('valor', 15, 2)->default(0);
                $table->string('moneda', 3)->default('COP');
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_proximo_pago')->nullable();
                $table->integer('dia_cobro')->nullable();
                $table->string('contacto_nombre', 150)->nullable();
                $table->string('contacto_email', 150)->nullable();
                $table->string('contacto_telefono', 40)->nullable();
                $table->text('notas')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->timestamp('deleted_at')->nullable();

                $table->foreign('empresa_id')->references('id')->on('empresa');
            });

            DB::statement("ALTER TABLE empresa_suscripcion ADD CONSTRAINT chk_suscripcion_tipo CHECK (tipo_plan IN ('UNICO', 'MENSUAL'))");
            DB::statement("ALTER TABLE empresa_suscripcion ADD CONSTRAINT chk_suscripcion_estado CHECK (estado IN ('PRUEBA', 'ACTIVA', 'SUSPENDIDA', 'CANCELADA'))");
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS ux_suscripcion_empresa ON empresa_suscripcion(empresa_id) WHERE deleted_at IS NULL');
        }

        if (!Schema::hasTable('suscripcion_pago')) {
            Schema::create('suscripcion_pago', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('suscripcion_id');
                $table->date('fecha_pago');
                $table->decimal('monto', 15, 2)->default(0);
                $table->string('metodo', 20)->nullable();
                $table->date('periodo_desde')->nullable();
                $table->date('periodo_hasta')->nullable();
                $table->string('referencia', 100)->nullable();
                $table->string('observacion', 255)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->timestamp('deleted_at')->nullable();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->foreign('suscripcion_id')->references('id')->on('empresa_suscripcion')->onDelete('cascade');
                $table->index('suscripcion_id', 'idx_suscripcion_pago_susc');
                $table->index(['empresa_id', 'fecha_pago'], 'idx_suscripcion_pago_empresa');
            });

            DB::statement("ALTER TABLE suscripcion_pago ADD CONSTRAINT chk_suscripcion_pago_metodo CHECK (metodo IN ('EFECTIVO', 'TRANSFERENCIA', 'TARJETA', 'PASARELA', 'OTRO'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripcion_pago');
        Schema::dropIfExists('empresa_suscripcion');
    }
};
