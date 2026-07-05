<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V83: Clasificación de horas en el detalle de asistencia (G2). Columnas
 * granulares que llena el motor de clasificación al digitar. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencia_frente_detalle', function (Blueprint $table) {
            if (!Schema::hasColumn('asistencia_frente_detalle', 'horas_ordinarias_diurnas'))       $table->decimal('horas_ordinarias_diurnas', 6, 2)->default(0);
            if (!Schema::hasColumn('asistencia_frente_detalle', 'horas_ordinarias_nocturnas'))     $table->decimal('horas_ordinarias_nocturnas', 6, 2)->default(0);
            if (!Schema::hasColumn('asistencia_frente_detalle', 'horas_dominicales_festivas'))     $table->decimal('horas_dominicales_festivas', 6, 2)->default(0);
            if (!Schema::hasColumn('asistencia_frente_detalle', 'horas_extra_diurnas_dom_fest'))   $table->decimal('horas_extra_diurnas_dom_fest', 6, 2)->default(0);
            if (!Schema::hasColumn('asistencia_frente_detalle', 'horas_extra_nocturnas_dom_fest')) $table->decimal('horas_extra_nocturnas_dom_fest', 6, 2)->default(0);
            if (!Schema::hasColumn('asistencia_frente_detalle', 'valor_hora_base'))                $table->decimal('valor_hora_base', 15, 2)->nullable();
            if (!Schema::hasColumn('asistencia_frente_detalle', 'valor_calculado_estimado'))       $table->decimal('valor_calculado_estimado', 15, 2)->nullable();
            if (!Schema::hasColumn('asistencia_frente_detalle', 'requiere_revision'))              $table->boolean('requiere_revision')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('asistencia_frente_detalle', function (Blueprint $table) {
            $table->dropColumn([
                'horas_ordinarias_diurnas',
                'horas_ordinarias_nocturnas',
                'horas_dominicales_festivas',
                'horas_extra_diurnas_dom_fest',
                'horas_extra_nocturnas_dom_fest',
                'valor_hora_base',
                'valor_calculado_estimado',
                'requiere_revision',
            ]);
        });
    }
};
