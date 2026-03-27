<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Empleados ─────────────────────────────────────────────────────────
        Schema::create('empleados', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('empresa_id')->index();
            $table->foreign('empresa_id')->references('id')->on('empresa');

            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('tipo_documento', 20)->default('CC');
            $table->string('numero_documento', 30);
            $table->string('cargo', 100)->nullable();
            $table->date('fecha_ingreso');
            $table->date('fecha_retiro')->nullable();
            $table->decimal('salario_base', 15, 2);
            $table->string('tipo_contrato', 30)->default('INDEFINIDO');
            $table->string('banco', 100)->nullable();
            $table->string('numero_cuenta', 50)->nullable();
            $table->string('tipo_cuenta', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        DB::statement("ALTER TABLE empleados ADD CONSTRAINT chk_empleado_tipo_doc
            CHECK (tipo_documento IN ('CC', 'CE', 'PASAPORTE', 'NIT'))");
        DB::statement("ALTER TABLE empleados ADD CONSTRAINT chk_empleado_contrato
            CHECK (tipo_contrato IN ('INDEFINIDO', 'FIJO', 'OBRA_LABOR', 'PRESTACION_SERVICIOS'))");

        DB::statement('CREATE INDEX idx_empleados_empresa ON empleados(empresa_id)');
        DB::statement('CREATE INDEX idx_empleados_doc ON empleados(numero_documento, empresa_id)');

        // ── Configuración de nómina por empresa ───────────────────────────────
        Schema::create('nomina_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('empresa_id')->unique();
            $table->foreign('empresa_id')->references('id')->on('empresa');

            $table->string('modo_nomina', 20)->default('SIMPLIFICADO');
            $table->string('periodicidad', 20)->default('MENSUAL');
            $table->decimal('smmlv', 15, 2)->default(1423500);
            $table->decimal('auxilio_transporte', 15, 2)->default(200000);
            $table->decimal('pct_salud_empleado', 5, 2)->default(4.00);
            $table->decimal('pct_pension_empleado', 5, 2)->default(4.00);
            $table->decimal('pct_salud_empleador', 5, 2)->default(8.50);
            $table->decimal('pct_pension_empleador', 5, 2)->default(12.00);
            $table->decimal('pct_caja_compensacion', 5, 2)->default(4.00);
            $table->decimal('pct_icbf', 5, 2)->default(3.00);
            $table->decimal('pct_sena', 5, 2)->default(2.00);
            $table->timestamps();
        });

        DB::statement("ALTER TABLE nomina_config ADD CONSTRAINT chk_nomina_config_modo
            CHECK (modo_nomina IN ('COMPLETO', 'SIMPLIFICADO'))");
        DB::statement("ALTER TABLE nomina_config ADD CONSTRAINT chk_nomina_config_periodicidad
            CHECK (periodicidad IN ('MENSUAL', 'QUINCENAL', 'SEMANAL'))");

        // ── Nivel de riesgo ARL por empleado ──────────────────────────────────
        Schema::create('empleado_arl', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('empresa_id');
            $table->foreign('empresa_id')->references('id')->on('empresa');

            $table->unsignedBigInteger('empleado_id');
            $table->foreign('empleado_id')->references('id')->on('empleados')->onDelete('cascade');

            $table->integer('nivel_riesgo')->default(1);
            $table->decimal('porcentaje', 5, 3)->default(0.522);
        });

        DB::statement('ALTER TABLE empleado_arl ADD CONSTRAINT chk_arl_nivel CHECK (nivel_riesgo BETWEEN 1 AND 5)');
        DB::statement('CREATE UNIQUE INDEX idx_empleado_arl_unico ON empleado_arl(empleado_id)');

        // ── Período de nómina ─────────────────────────────────────────────────
        Schema::create('periodo_nomina', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('empresa_id');
            $table->foreign('empresa_id')->references('id')->on('empresa');

            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('estado', 20)->default('ABIERTO');
            $table->timestamp('created_at')->useCurrent();

            $table->index('empresa_id', 'idx_periodo_nomina_empresa');
        });

        DB::statement("ALTER TABLE periodo_nomina ADD CONSTRAINT chk_periodo_estado
            CHECK (estado IN ('ABIERTO', 'LIQUIDADO', 'PAGADO', 'ANULADO'))");

        // ── Nómina (cabecera por empleado por período) ─────────────────────────
        Schema::create('nomina', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('empresa_id');
            $table->foreign('empresa_id')->references('id')->on('empresa');

            $table->unsignedBigInteger('periodo_id');
            $table->foreign('periodo_id')->references('id')->on('periodo_nomina');

            $table->unsignedBigInteger('empleado_id');
            $table->foreign('empleado_id')->references('id')->on('empleados');

            $table->decimal('salario_base', 15, 2);
            $table->integer('dias_trabajados')->default(30);

            // Devengados
            $table->decimal('salario_proporcional', 15, 2)->default(0);
            $table->decimal('auxilio_transporte', 15, 2)->default(0);
            $table->decimal('total_novedades_dev', 15, 2)->default(0);
            $table->decimal('total_devengado', 15, 2)->default(0);

            // Deducciones empleado
            $table->decimal('deduccion_salud', 15, 2)->default(0);
            $table->decimal('deduccion_pension', 15, 2)->default(0);
            $table->decimal('deduccion_otros', 15, 2)->default(0);
            $table->decimal('total_deducciones', 15, 2)->default(0);

            // Neto a pagar
            $table->decimal('neto_pagar', 15, 2)->default(0);

            // Aportes empleador (solo modo COMPLETO)
            $table->decimal('aporte_salud', 15, 2)->default(0);
            $table->decimal('aporte_pension', 15, 2)->default(0);
            $table->decimal('aporte_arl', 15, 2)->default(0);
            $table->decimal('aporte_caja', 15, 2)->default(0);
            $table->decimal('aporte_icbf', 15, 2)->default(0);
            $table->decimal('aporte_sena', 15, 2)->default(0);

            // Provisiones mensuales (solo modo COMPLETO)
            $table->decimal('provision_prima', 15, 2)->default(0);
            $table->decimal('provision_cesantias', 15, 2)->default(0);
            $table->decimal('provision_int_cesantias', 15, 2)->default(0);
            $table->decimal('provision_vacaciones', 15, 2)->default(0);

            $table->string('estado', 20)->default('BORRADOR');
            $table->timestamps();

            $table->unique(['empleado_id', 'periodo_id'], 'uq_nomina_empleado_periodo');
            $table->index('empresa_id', 'idx_nomina_empresa');
            $table->index('periodo_id', 'idx_nomina_periodo');
            $table->index('empleado_id', 'idx_nomina_empleado');
        });

        DB::statement("ALTER TABLE nomina ADD CONSTRAINT chk_nomina_estado
            CHECK (estado IN ('BORRADOR', 'APROBADO', 'PAGADO', 'ANULADO'))");

        // ── Novedades de nómina ───────────────────────────────────────────────
        Schema::create('nomina_novedad', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('nomina_id');
            $table->foreign('nomina_id')->references('id')->on('nomina')->onDelete('cascade');

            $table->string('tipo', 40);
            $table->string('descripcion', 200)->nullable();
            $table->decimal('cantidad', 10, 2)->default(1);
            $table->decimal('valor_unitario', 15, 2);
            $table->decimal('valor_total', 15, 2);
            $table->boolean('es_deduccion')->default(false);

            $table->index('nomina_id', 'idx_nomina_novedad_nomina');
        });

        DB::statement("ALTER TABLE nomina_novedad ADD CONSTRAINT chk_novedad_tipo CHECK (
            tipo IN (
                'HORA_EXTRA_DIURNA', 'HORA_EXTRA_NOCTURNA', 'HORA_EXTRA_DOMINICAL',
                'HORA_EXTRA_FESTIVO', 'INCAPACIDAD', 'LICENCIA_REMUNERADA',
                'BONO', 'COMISION', 'PRESTAMO', 'EMBARGO', 'OTRO_DEVENGO', 'OTRO_DESCUENTO'
            )
        )");
    }

    public function down(): void
    {
        // Eliminar en orden inverso por dependencias de FK
        Schema::dropIfExists('nomina_novedad');
        Schema::dropIfExists('nomina');
        Schema::dropIfExists('periodo_nomina');
        Schema::dropIfExists('empleado_arl');
        Schema::dropIfExists('nomina_config');
        Schema::dropIfExists('empleados');
    }
};