<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V88 · E3 · Estados de comprobante y modo revisión del contador.
 * AUTOMATICO (default): todo asiento nace CONTABILIZADO (igual que hoy).
 * REVISION: los asientos automáticos nacen BORRADOR y el contador los aprueba;
 * BORRADOR no suma en reportes oficiales. contabilidad_posting_log = vista
 * positiva de auditoría del posting automático (solo INSERT). Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE empresa ADD COLUMN IF NOT EXISTS modo_contabilizacion VARCHAR(15) NOT NULL DEFAULT 'AUTOMATICO'");

        if (!Schema::hasTable('contabilidad_posting_log')) {
            Schema::create('contabilidad_posting_log', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->string('tipo_origen', 30);
                $table->unsignedBigInteger('origen_id');
                $table->unsignedBigInteger('asiento_id')->nullable();
                $table->string('estado', 10);              // EXITO | ERROR
                $table->string('error', 500)->nullable();
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        DB::statement('CREATE INDEX IF NOT EXISTS idx_contab_posting_log_empresa ON contabilidad_posting_log (empresa_id, created_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('contabilidad_posting_log');
        DB::statement('ALTER TABLE empresa DROP COLUMN IF EXISTS modo_contabilizacion');
    }
};
