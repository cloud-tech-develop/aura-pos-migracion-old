<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V130 — vacaciones saldo.
 *
 * Traducción fiel de la migración Flyway V130__vacaciones_saldo.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- F3 — Vacaciones con saldo.
--
--   nomina_config.permite_vacaciones_anticipadas → si FALSE (default), no se
--       pueden tomar más días de los causados; si TRUE, solo advierte.
--   empleados.vacaciones_saldo_inicial → días de vacaciones que el empleado trae
--       de su sistema anterior (las empresas migran). Suma al saldo causado.

ALTER TABLE nomina_config
    ADD COLUMN IF NOT EXISTS permite_vacaciones_anticipadas BOOLEAN NOT NULL DEFAULT FALSE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE empleados
    ADD COLUMN IF NOT EXISTS vacaciones_saldo_inicial NUMERIC(6,2) NOT NULL DEFAULT 0
MIG_SQL);
    }

    public function down(): void
    {
        // Migración de datos/columnas (ver up). Reversa no automática.
    }
};
