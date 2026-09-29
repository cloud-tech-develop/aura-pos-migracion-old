<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V82: Parametrización laboral (G1). Jornada legal por vigencia + calendario
 * laboral (festivos) + turno por frente + horas ordinarias del turno. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('jornada_laboral_config')) {
            Schema::create('jornada_laboral_config', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->date('fecha_inicio_vigencia');
                $table->date('fecha_fin_vigencia')->nullable();
                $table->decimal('horas_semanales_legales', 5, 2)->default(42);
                $table->decimal('horas_mensuales_base', 6, 2)->default(210);
                $table->time('hora_diurna_inicio')->default('06:00');
                $table->time('hora_diurna_fin')->default('19:00');
                $table->time('hora_nocturna_inicio')->default('19:00');
                $table->time('hora_nocturna_fin')->default('06:00');
                $table->decimal('recargo_nocturno', 5, 2)->default(35);
                $table->decimal('recargo_extra_diurna', 5, 2)->default(25);
                $table->decimal('recargo_extra_nocturna', 5, 2)->default(75);
                $table->decimal('recargo_dominical_festivo', 5, 2)->default(90);
                $table->decimal('max_horas_extra_dia', 5, 2)->default(2);
                $table->decimal('max_horas_extra_semana', 5, 2)->default(12);
                $table->boolean('aplica_excepcion_sectorial')->default(false);
                $table->string('sector_excepcion', 80)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->timestamp('deleted_at')->nullable();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->index(['empresa_id', 'fecha_inicio_vigencia'], 'idx_jornada_config_empresa_vig');
            });
        }

        if (!Schema::hasTable('calendario_laboral')) {
            Schema::create('calendario_laboral', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->date('fecha');
                $table->string('tipo_dia', 30);
                $table->string('nombre', 150)->nullable();
                $table->boolean('aplica_recargo')->default(true);
                $table->boolean('es_festivo_nacional')->default(false);
                $table->boolean('es_festivo_regional')->default(false);
                $table->boolean('es_descanso_empresa')->default(false);
                $table->string('origen', 20)->default('MANUAL');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->timestamp('deleted_at')->nullable();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->index(['empresa_id', 'fecha'], 'idx_calendario_empresa_fecha');
            });

            DB::statement("ALTER TABLE calendario_laboral ADD CONSTRAINT chk_calendario_tipo CHECK (tipo_dia IN ('LABORAL', 'DOMINGO', 'FESTIVO_NACIONAL', 'FESTIVO_REGIONAL', 'DESCANSO_EMPRESA', 'CIERRE_OPERATIVO', 'COMPENSATORIO'))");
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS ux_calendario_empresa_fecha ON calendario_laboral(empresa_id, fecha) WHERE deleted_at IS NULL');
        }

        if (!Schema::hasTable('frente_turno')) {
            Schema::create('frente_turno', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('proyecto_id');
                $table->unsignedBigInteger('frente_id');
                $table->unsignedBigInteger('turno_id');
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_fin')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->timestamp('deleted_at')->nullable();

                $table->foreign('empresa_id')->references('id')->on('empresa');
                $table->foreign('proyecto_id')->references('id')->on('proyecto');
                $table->foreign('frente_id')->references('id')->on('proyecto_frente');
                $table->foreign('turno_id')->references('id')->on('turno_trabajo');
                $table->index('frente_id', 'idx_frente_turno_frente');
            });
        }

        Schema::table('turno_trabajo', function (Blueprint $table) {
            if (!Schema::hasColumn('turno_trabajo', 'horas_ordinarias_programadas')) {
                $table->decimal('horas_ordinarias_programadas', 5, 2)->default(8);
            }
        });
    }

    public function down(): void
    {
        Schema::table('turno_trabajo', function (Blueprint $table) {
            $table->dropColumn('horas_ordinarias_programadas');
        });
        Schema::dropIfExists('frente_turno');
        Schema::dropIfExists('calendario_laboral');
        Schema::dropIfExists('jornada_laboral_config');
    }
};
