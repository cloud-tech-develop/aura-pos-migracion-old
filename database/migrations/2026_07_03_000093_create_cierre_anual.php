<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V93 · E8 · Cierre anual fiscal (C9): provisión de renta, traslado de utilidad
 * al abrir el año y distribución de utilidades post-asamblea (reserva legal,
 * dividendos y su pago). El sistema SUGIERE valores; el contador DIGITA. Crea
 * cuentas PUC faltantes para empresas existentes. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Operaciones del cierre de ejercicio (provisión renta / traslado) ──
        if (!Schema::hasTable('cierre_anual')) {
            Schema::create('cierre_anual', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->integer('anio');
                $table->string('tipo', 20);                     // PROVISION_RENTA | TRASLADO
                $table->decimal('monto', 18, 2);                // TRASLADO: >0 utilidad, <0 pérdida
                $table->string('detalle', 300)->nullable();
                $table->date('fecha');
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['empresa_id', 'anio', 'tipo']);
            });
        }

        // ── Distribución de utilidades (post-asamblea) ────────────────────────
        if (!Schema::hasTable('distribucion_utilidades')) {
            Schema::create('distribucion_utilidades', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->integer('anio');
                $table->decimal('utilidad_base', 18, 2);        // saldo 3705 al momento de distribuir
                $table->decimal('reserva_legal', 18, 2)->default(0);
                $table->decimal('dividendos', 18, 2)->default(0);
                $table->string('observaciones', 300)->nullable();
                $table->date('fecha');
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['empresa_id', 'anio']);
            });
        }

        if (!Schema::hasTable('dividendo_pago')) {
            Schema::create('dividendo_pago', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('distribucion_id');
                $table->decimal('monto', 18, 2);
                $table->string('metodo_pago', 40)->nullable();
                $table->unsignedBigInteger('cuenta_bancaria_id')->nullable();
                $table->unsignedBigInteger('tercero_id')->nullable();
                $table->date('fecha');
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('distribucion_id')->references('id')->on('distribucion_utilidades');
                $table->index('distribucion_id', 'idx_dividendo_pago_distribucion');
            });
        }

        // ── Cuentas nuevas para empresas EXISTENTES (idempotente) ────────────
        // Grupos nivel 2 que faltan: 54 (impuesto de renta) y 33 (reservas).
        DB::statement(<<<'SQL'
            INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
            SELECT c.empresa_id, '54', 'Impuesto de Renta y Complementarios', 'GASTO', 'DEBITO', 2, c.id, TRUE, FALSE, now()
            FROM plan_cuenta c WHERE c.codigo = '5'
              AND NOT EXISTS (SELECT 1 FROM plan_cuenta x WHERE x.empresa_id = c.empresa_id AND x.codigo = '54')
        SQL);

        DB::statement(<<<'SQL'
            INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
            SELECT c.empresa_id, '33', 'Reservas', 'PATRIMONIO', 'CREDITO', 2, c.id, TRUE, FALSE, now()
            FROM plan_cuenta c WHERE c.codigo = '3'
              AND NOT EXISTS (SELECT 1 FROM plan_cuenta x WHERE x.empresa_id = c.empresa_id AND x.codigo = '33')
        SQL);

        // Cuentas de movimiento nivel 3.
        DB::statement(<<<'SQL'
            INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
            SELECT p.empresa_id, v.codigo, v.nombre, v.tipo, v.naturaleza, 3, p.id, TRUE, TRUE, now()
            FROM (VALUES ('5405', 'Impuesto de Renta y Complementarios', 'GASTO',      'DEBITO',  '54'),
                         ('2404', 'Impuesto de Renta por Pagar',         'PASIVO',     'CREDITO', '24'),
                         ('2360', 'Dividendos o Participaciones por Pagar','PASIVO',   'CREDITO', '23'),
                         ('3305', 'Reservas Obligatorias',               'PATRIMONIO', 'CREDITO', '33')
                 ) AS v(codigo, nombre, tipo, naturaleza, padre)
            JOIN plan_cuenta p ON p.codigo = v.padre
            WHERE NOT EXISTS (SELECT 1 FROM plan_cuenta x
                              WHERE x.empresa_id = p.empresa_id AND x.codigo = v.codigo)
        SQL);

        // Auxiliar nivel 4: la reserva legal cuelga de 3305.
        DB::statement(<<<'SQL'
            INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
            SELECT p.empresa_id, '330505', 'Reserva Legal', 'PATRIMONIO', 'CREDITO', 4, p.id, TRUE, TRUE, now()
            FROM plan_cuenta p WHERE p.codigo = '3305'
              AND NOT EXISTS (SELECT 1 FROM plan_cuenta x WHERE x.empresa_id = p.empresa_id AND x.codigo = '330505')
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('dividendo_pago');
        Schema::dropIfExists('distribucion_utilidades');
        Schema::dropIfExists('cierre_anual');
        // Nota: las cuentas PUC insertadas no se revierten (datos).
    }
};
