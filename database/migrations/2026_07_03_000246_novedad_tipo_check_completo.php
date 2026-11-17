<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V246 — chk_novedad_tipo con todos los tipos que genera el sistema (V119 había
 * quitado los de asistencia y nunca tuvo RETROACTIVO).
 *
 * Traducción de la migración Flyway V246__novedad_tipo_check_completo.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE nomina_novedad DROP CONSTRAINT IF EXISTS chk_novedad_tipo');

        DB::statement(<<<'MIG_SQL'
ALTER TABLE nomina_novedad
    ADD CONSTRAINT chk_novedad_tipo CHECK (tipo IN (
        'HORA_EXTRA_DIURNA', 'HORA_EXTRA_NOCTURNA', 'HORA_EXTRA_DOMINICAL', 'HORA_EXTRA_FESTIVO',
        'HORA_EXTRA_DOMINICAL_FESTIVA', 'RECARGO_NOCTURNO', 'RECARGO_DOMINICAL_FESTIVO',
        'AUSENCIA_NO_JUSTIFICADA', 'LLEGADA_TARDE_DESCONTADA',
        'INCAPACIDAD', 'LICENCIA_REMUNERADA', 'LICENCIA_NO_REMUNERADA', 'LICENCIA_MATERNIDAD',
        'VACACIONES', 'SUSPENSION',
        'BONO', 'COMISION', 'PRESTAMO', 'EMBARGO', 'OTRO_DEVENGO', 'OTRO_DESCUENTO',
        'RETROACTIVO'
    ))
MIG_SQL);
    }

    public function down(): void
    {
        // Sin reversa: volver a la lista de V119 rompería las liquidaciones con asistencia.
    }
};
