<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V64: Catálogo de conceptos de caja. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('concepto_caja')) {
            return;
        }

        Schema::create('concepto_caja', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->string('nombre', 120);
            $table->string('tipo', 10); // INGRESO | EGRESO
            $table->unsignedBigInteger('cuenta_contable_id');
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['empresa_id', 'tipo', 'activo'], 'ix_concepto_caja_empresa_tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concepto_caja');
    }
};
