<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V86 · E2 · Pieza 2 de tesorería: formas de pago parametrizables.
 * Cada método de pago (EFECTIVO, TRANSFERENCIA, NEQUI…) puede mapearse a una
 * cuenta contable del disponible (11xx). El motor resuelve:
 * cuenta bancaria → forma de pago → fallback CAJA/BANCOS. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('forma_pago_contable')) {
            Schema::create('forma_pago_contable', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->string('codigo', 40);
                $table->string('nombre', 80);
                $table->unsignedBigInteger('cuenta_contable_id')->nullable();
                $table->boolean('requiere_cuenta_bancaria')->default(false);
                $table->boolean('activo')->default(true);
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['empresa_id', 'codigo']);
                $table->index(['empresa_id', 'activo'], 'idx_forma_pago_contable_empresa');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('forma_pago_contable');
    }
};
