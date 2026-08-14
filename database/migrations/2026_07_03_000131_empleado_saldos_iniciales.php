<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V131 — empleado saldos iniciales.
 *
 * Traducción fiel de la migración Flyway V131__empleado_saldos_iniciales.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- F7 — Saldos iniciales. Las empresas que migran de otro sistema traen valores
-- acumulados que el motor no puede recalcular desde cero.
--
--   cesantias_saldo_inicial   → cesantías acumuladas y no consignadas al fondo.
--   ingresos_ytd              → ingresos del año en curso antes de entrar a Aura
--                               (para retefuente procedimiento 2 y el certificado
--                               de ingresos y retenciones).
--   retenciones_ytd           → retención en la fuente ya practicada en el año.
--
-- vacaciones_saldo_inicial ya se agregó en V130.

ALTER TABLE empleados
    ADD COLUMN IF NOT EXISTS cesantias_saldo_inicial NUMERIC(15,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS ingresos_ytd            NUMERIC(15,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS retenciones_ytd         NUMERIC(15,2) NOT NULL DEFAULT 0
MIG_SQL);
    }

    public function down(): void
    {
        // Migración de datos/columnas (ver up). Reversa no automática.
    }
};
