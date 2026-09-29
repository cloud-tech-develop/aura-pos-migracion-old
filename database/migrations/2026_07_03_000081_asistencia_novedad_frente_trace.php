<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V81: Trazabilidad de novedades generadas desde asistencia por FRENTE (Fase E).
 * Reutiliza la staging asistencia_novedad_nomina con origen = 'PROYECTO_FRENTE'.
 * Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencia_novedad_nomina', function (Blueprint $table) {
            if (!Schema::hasColumn('asistencia_novedad_nomina', 'proyecto_id'))                  $table->unsignedBigInteger('proyecto_id')->nullable();
            if (!Schema::hasColumn('asistencia_novedad_nomina', 'frente_id'))                    $table->unsignedBigInteger('frente_id')->nullable();
            if (!Schema::hasColumn('asistencia_novedad_nomina', 'asistencia_frente_id'))         $table->unsignedBigInteger('asistencia_frente_id')->nullable();
            if (!Schema::hasColumn('asistencia_novedad_nomina', 'asistencia_frente_detalle_id')) $table->unsignedBigInteger('asistencia_frente_detalle_id')->nullable();
            if (!Schema::hasColumn('asistencia_novedad_nomina', 'soporte_pdf_id'))               $table->unsignedBigInteger('soporte_pdf_id')->nullable();
        });

        DB::statement('CREATE INDEX IF NOT EXISTS idx_asis_nov_frente ON asistencia_novedad_nomina(asistencia_frente_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_asis_nov_frente');
        Schema::table('asistencia_novedad_nomina', function (Blueprint $table) {
            $table->dropColumn([
                'proyecto_id',
                'frente_id',
                'asistencia_frente_id',
                'asistencia_frente_detalle_id',
                'soporte_pdf_id',
            ]);
        });
    }
};
