<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V129 — novedad dias ausencia.
 *
 * Traducción fiel de la migración Flyway V129__novedad_dias_ausencia.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- F0 — Conciliación de días. Una novedad de ausencia (incapacidad, licencia no
-- remunerada, vacaciones, licencia de maternidad) descuenta días de salario sola.
--
--   dias                 → días que abarca la novedad (para las de ausencia).
--   afecta_dias_salario  → si TRUE, sus días se restan del salario proporcional.
--
-- El pago propio de la novedad (valor de incapacidad, de vacaciones) sigue siendo
-- una línea de devengado aparte: aquí solo se modela el efecto sobre los días.

ALTER TABLE nomina_novedad
    ADD COLUMN IF NOT EXISTS dias INTEGER,
    ADD COLUMN IF NOT EXISTS afecta_dias_salario BOOLEAN NOT NULL DEFAULT FALSE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Backfill: marcar las novedades de ausencia ya existentes.
UPDATE nomina_novedad
   SET afecta_dias_salario = TRUE
 WHERE tipo IN ('INCAPACIDAD', 'LICENCIA_NO_REMUNERADA', 'VACACIONES', 'LICENCIA_MATERNIDAD')
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Backfill: derivar los días desde el rango de fechas cuando exista.
UPDATE nomina_novedad
   SET dias = (fecha_fin - fecha_inicio) + 1
 WHERE afecta_dias_salario = TRUE
   AND fecha_inicio IS NOT NULL
   AND fecha_fin IS NOT NULL
   AND dias IS NULL
MIG_SQL);
    }

    public function down(): void
    {
        // Migración de datos/columnas (ver up). Reversa no automática.
    }
};
