<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V67: Nómina Fase 0 — modo de liquidación y control de asistencia. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nomina_config', function (Blueprint $table) {
            if (!Schema::hasColumn('nomina_config', 'modo_liquidacion')) {
                $table->string('modo_liquidacion', 30)->default('SIN_ASISTENCIA'); // SIN_ASISTENCIA | CON_ASISTENCIA_OBLIGATORIA | MIXTA
            }
        });

        // Constraint idempotente (drop-if-exists + add).
        DB::statement("ALTER TABLE nomina_config DROP CONSTRAINT IF EXISTS chk_nomina_config_modo_liq");
        DB::statement("ALTER TABLE nomina_config ADD CONSTRAINT chk_nomina_config_modo_liq CHECK (modo_liquidacion IN ('SIN_ASISTENCIA', 'CON_ASISTENCIA_OBLIGATORIA', 'MIXTA'))");

        Schema::table('empleados', function (Blueprint $table) {
            if (!Schema::hasColumn('empleados', 'requiere_control_asistencia')) {
                $table->boolean('requiere_control_asistencia')->default(false);
            }
        });
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE nomina_config DROP CONSTRAINT IF EXISTS chk_nomina_config_modo_liq");
        Schema::table('nomina_config', function (Blueprint $table) {
            $table->dropColumn('modo_liquidacion');
        });
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn('requiere_control_asistencia');
        });
    }
};
