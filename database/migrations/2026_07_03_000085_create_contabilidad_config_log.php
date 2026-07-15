<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V85 · E1 · Guardarraíles de parametrización contable.
 * Auditoría de cambios del mapeo concepto→cuenta (cuenta_config): quién cambió
 * qué concepto, de qué cuenta a cuál y cuándo. Solo INSERT, nunca UPDATE/DELETE:
 * la historia es inmutable. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('contabilidad_config_log')) {
            Schema::create('contabilidad_config_log', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->string('concepto', 40);
                $table->unsignedBigInteger('cuenta_anterior_id')->nullable();
                $table->unsignedBigInteger('cuenta_nueva_id');
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        DB::statement('CREATE INDEX IF NOT EXISTS idx_contabilidad_config_log_empresa ON contabilidad_config_log (empresa_id, created_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('contabilidad_config_log');
    }
};
