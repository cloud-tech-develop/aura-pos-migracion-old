<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V91 · E6 · Ajustes de devengo (C8): anticipos, gastos diferidos, causaciones
 * programadas y deterioro de cartera. NIIF exige base de acumulación: sin esto
 * la contabilidad solo refleja caja y documentos. Crea cuentas PUC faltantes
 * para empresas existentes. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Anticipos (cliente 2805 / proveedor 1330) ────────────────────────
        if (!Schema::hasTable('anticipo')) {
            Schema::create('anticipo', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->string('tipo', 10);                     // CLIENTE | PROVEEDOR
                $table->unsignedBigInteger('tercero_id');
                $table->decimal('monto', 18, 2);
                $table->decimal('saldo', 18, 2);
                $table->string('metodo_pago', 40)->nullable();
                $table->unsignedBigInteger('cuenta_bancaria_id')->nullable();
                $table->date('fecha');
                $table->string('observaciones', 300)->nullable();
                $table->string('estado', 10)->default('ACTIVO'); // ACTIVO|APLICADO|ANULADO
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['empresa_id', 'tercero_id', 'estado'], 'idx_anticipo_empresa_tercero');
            });
        }

        if (!Schema::hasTable('anticipo_cruce')) {
            Schema::create('anticipo_cruce', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('anticipo_id');
                $table->unsignedBigInteger('cuenta_cobrar_id')->nullable();
                $table->unsignedBigInteger('cuenta_pagar_id')->nullable();
                $table->decimal('monto', 18, 2);
                $table->date('fecha');
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('anticipo_id')->references('id')->on('anticipo');
            });
        }

        // ── Gastos diferidos (1705) ──────────────────────────────────────────
        DB::statement('ALTER TABLE gasto ADD COLUMN IF NOT EXISTS es_diferido BOOLEAN NOT NULL DEFAULT FALSE');
        DB::statement('ALTER TABLE gasto ADD COLUMN IF NOT EXISTS meses_diferido INT');

        if (!Schema::hasTable('diferido_amortizacion')) {
            Schema::create('diferido_amortizacion', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('gasto_id');
                $table->string('periodo', 7);                   // 'yyyy-MM'
                $table->decimal('monto', 18, 2);
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['gasto_id', 'periodo']);
            });
        }

        // ── Causaciones programadas (asiento recurrente en BORRADOR) ─────────
        if (!Schema::hasTable('causacion_programada')) {
            Schema::create('causacion_programada', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->string('nombre', 120);
                $table->integer('dia')->default(1);             // día del mes que se genera
                $table->boolean('activa')->default(true);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('causacion_programada_linea')) {
            Schema::create('causacion_programada_linea', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('causacion_id');
                $table->unsignedBigInteger('cuenta_id');
                $table->string('descripcion', 200)->nullable();
                $table->decimal('debito', 18, 2)->default(0);
                $table->decimal('credito', 18, 2)->default(0);
                $table->unsignedBigInteger('tercero_id')->nullable();

                $table->foreign('causacion_id')->references('id')->on('causacion_programada');
            });
        }

        if (!Schema::hasTable('causacion_ejecucion')) {
            Schema::create('causacion_ejecucion', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('causacion_id');
                $table->string('periodo', 7);
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['causacion_id', 'periodo']);
                $table->foreign('causacion_id')->references('id')->on('causacion_programada');
            });
        }

        // ── Deterioro de cartera (propuesta que el contador aprueba) ─────────
        if (!Schema::hasTable('deterioro_calculo')) {
            Schema::create('deterioro_calculo', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->date('fecha');
                $table->decimal('monto', 18, 2);
                $table->string('detalle', 500)->nullable();
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // ── Cuentas nuevas para empresas EXISTENTES (idempotente) ────────────
        // Grupos nivel 2 que faltan: 17 (diferidos) y 28 (anticipos recibidos).
        DB::statement(<<<'SQL'
            INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
            SELECT c.empresa_id, '17', 'Diferidos', 'ACTIVO', 'DEBITO', 2, c.id, TRUE, FALSE, now()
            FROM plan_cuenta c WHERE c.codigo = '1'
              AND NOT EXISTS (SELECT 1 FROM plan_cuenta x WHERE x.empresa_id = c.empresa_id AND x.codigo = '17')
        SQL);

        DB::statement(<<<'SQL'
            INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
            SELECT c.empresa_id, '28', 'Otros Pasivos', 'PASIVO', 'CREDITO', 2, c.id, TRUE, FALSE, now()
            FROM plan_cuenta c WHERE c.codigo = '2'
              AND NOT EXISTS (SELECT 1 FROM plan_cuenta x WHERE x.empresa_id = c.empresa_id AND x.codigo = '28')
        SQL);

        // Cuentas de movimiento nivel 3.
        DB::statement(<<<'SQL'
            INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
            SELECT p.empresa_id, v.codigo, v.nombre, v.tipo, v.naturaleza, 3, p.id, TRUE, TRUE, now()
            FROM (VALUES ('1330', 'Anticipos a Proveedores',        'ACTIVO', 'DEBITO',  '13'),
                         ('1399', 'Provisión Cartera (deterioro)',  'ACTIVO', 'CREDITO', '13'),
                         ('1499', 'Provisión Inventarios',          'ACTIVO', 'CREDITO', '14'),
                         ('1705', 'Gastos Pagados por Anticipado',  'ACTIVO', 'DEBITO',  '17'),
                         ('2805', 'Anticipos de Clientes',          'PASIVO', 'CREDITO', '28'),
                         ('5199', 'Provisiones y Deterioros',       'GASTO',  'DEBITO',  '51')
                 ) AS v(codigo, nombre, tipo, naturaleza, padre)
            JOIN plan_cuenta p ON p.codigo = v.padre
            WHERE NOT EXISTS (SELECT 1 FROM plan_cuenta x
                              WHERE x.empresa_id = p.empresa_id AND x.codigo = v.codigo)
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE gasto DROP COLUMN IF EXISTS es_diferido');
        DB::statement('ALTER TABLE gasto DROP COLUMN IF EXISTS meses_diferido');
        Schema::dropIfExists('deterioro_calculo');
        Schema::dropIfExists('causacion_ejecucion');
        Schema::dropIfExists('causacion_programada_linea');
        Schema::dropIfExists('causacion_programada');
        Schema::dropIfExists('diferido_amortizacion');
        Schema::dropIfExists('anticipo_cruce');
        Schema::dropIfExists('anticipo');
        // Nota: las cuentas PUC insertadas no se revierten (datos).
    }
};
