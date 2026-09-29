<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V94 · E9 · Conciliación bancaria (C6): extracto importado del banco, matching
 * sugerido contra el libro (movimientos de la cuenta contable del banco) y
 * ajustes desde la misma pantalla (comisiones, GMF 4x1000, intereses). Crea
 * cuentas PUC faltantes para empresas existentes. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Extracto y sus líneas ─────────────────────────────────────────────
        if (!Schema::hasTable('extracto_bancario')) {
            Schema::create('extracto_bancario', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('cuenta_bancaria_id');
                $table->string('periodo', 7);                   // '2026-07'
                $table->decimal('saldo_inicial', 18, 2)->default(0);
                $table->decimal('saldo_final', 18, 2)->default(0);
                $table->string('estado', 15)->default('ABIERTO'); // ABIERTO | CONCILIADO
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->timestamp('conciliado_at')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['empresa_id', 'cuenta_bancaria_id', 'periodo']);
                $table->foreign('cuenta_bancaria_id')->references('id')->on('cuenta_bancaria');
            });
        }

        if (!Schema::hasTable('extracto_linea')) {
            Schema::create('extracto_linea', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('extracto_id');
                $table->date('fecha');
                $table->string('descripcion', 255)->nullable();
                $table->decimal('valor', 18, 2);                // >0 abono del banco / <0 cargo
                $table->string('estado', 15)->default('PENDIENTE'); // PENDIENTE | CONCILIADO | AJUSTE
                $table->unsignedBigInteger('asiento_detalle_id')->nullable(); // match confirmado
                $table->string('tipo_ajuste', 20)->nullable();  // GASTO_BANCARIO | GMF | INTERES
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('extracto_id')->references('id')->on('extracto_bancario');
                $table->index('extracto_id', 'idx_extracto_linea_extracto');
                $table->index('asiento_detalle_id', 'idx_extracto_linea_detalle');
            });
        }

        // ── Cuentas nuevas para empresas EXISTENTES (idempotente) ────────────
        // Grupos nivel 2 por si faltan: 53 (gastos no op.) y 42 (ingresos no op.).
        DB::statement(<<<'SQL'
            INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
            SELECT c.empresa_id, '53', 'Gastos No Operacionales', 'GASTO', 'DEBITO', 2, c.id, TRUE, FALSE, now()
            FROM plan_cuenta c WHERE c.codigo = '5'
              AND NOT EXISTS (SELECT 1 FROM plan_cuenta x WHERE x.empresa_id = c.empresa_id AND x.codigo = '53')
        SQL);

        DB::statement(<<<'SQL'
            INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
            SELECT c.empresa_id, '42', 'No Operacionales', 'INGRESO', 'CREDITO', 2, c.id, TRUE, FALSE, now()
            FROM plan_cuenta c WHERE c.codigo = '4'
              AND NOT EXISTS (SELECT 1 FROM plan_cuenta x WHERE x.empresa_id = c.empresa_id AND x.codigo = '42')
        SQL);

        // Cuentas de movimiento nivel 3.
        DB::statement(<<<'SQL'
            INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
            SELECT p.empresa_id, v.codigo, v.nombre, v.tipo, v.naturaleza, 3, p.id, TRUE, TRUE, now()
            FROM (VALUES ('5305', 'Financieros',  'GASTO',   'DEBITO',  '53'),
                         ('4210', 'Financieros',  'INGRESO', 'CREDITO', '42')
                 ) AS v(codigo, nombre, tipo, naturaleza, padre)
            JOIN plan_cuenta p ON p.codigo = v.padre
            WHERE NOT EXISTS (SELECT 1 FROM plan_cuenta x
                              WHERE x.empresa_id = p.empresa_id AND x.codigo = v.codigo)
        SQL);

        // Auxiliares nivel 4: comisiones y GMF bajo 5305, intereses bajo 4210.
        DB::statement(<<<'SQL'
            INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
            SELECT p.empresa_id, v.codigo, v.nombre, v.tipo, v.naturaleza, 4, p.id, TRUE, TRUE, now()
            FROM (VALUES ('530515', 'Comisiones',                             'GASTO',   'DEBITO',  '5305'),
                         ('530595', 'Gravamen a los Movimientos Financieros', 'GASTO',   'DEBITO',  '5305'),
                         ('421005', 'Intereses',                              'INGRESO', 'CREDITO', '4210')
                 ) AS v(codigo, nombre, tipo, naturaleza, padre)
            JOIN plan_cuenta p ON p.codigo = v.padre
            WHERE NOT EXISTS (SELECT 1 FROM plan_cuenta x
                              WHERE x.empresa_id = p.empresa_id AND x.codigo = v.codigo)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('extracto_linea');
        Schema::dropIfExists('extracto_bancario');
        // Nota: las cuentas PUC insertadas no se revierten (datos).
    }
};
