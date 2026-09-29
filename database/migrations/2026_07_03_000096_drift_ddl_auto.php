<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V96 — drift ddl auto.
 *
 * Traducción fiel de la migración Flyway V96__drift_ddl_auto.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V96: Drift de ddl-auto=update sobre tablas ya migradas ──────────────────
--
-- Contexto (ADR-005 / auditoría scripts/audit_baseline.py):
-- Estas columnas existen en las entidades JPA y en las BD actuales, pero
-- ninguna migración las crea. Las agregó el viejo `ddl-auto=update` DESPUÉS
-- de que sus tablas ya se creaban por migración (devolucion=V43, cuenta_bancaria=V36).
--
-- Efecto por ambiente:
--   · BD existente  → las columnas ya están: IF NOT EXISTS las ignora. No-op.
--   · BD desde cero → V43/V36 crean la tabla sin ellas; esta migración las agrega.
--
-- NOTA: esta migración por sí sola NO arregla la instalación desde cero.
-- Falta V13__baseline.sql con las 55 tablas pre-V14 (tercero, venta, producto,
-- empresa, usuario, ...). Ver Fase −1 del PLAN_MIGRACION_NOMINA.md.

-- ── devolucion (tabla creada en V43) ────────────────────────────────────────
-- Campos de "cambio de producto": lo que el cliente se lleva a cambio de lo
-- que devuelve. neto_diferencia = total_devolucion - total_agregado.
ALTER TABLE devolucion
    ADD COLUMN IF NOT EXISTS fecha_devolucion DATE,
    ADD COLUMN IF NOT EXISTS total_agregado   NUMERIC(18,2),
    ADD COLUMN IF NOT EXISTS iva_agregado     NUMERIC(18,2),
    ADD COLUMN IF NOT EXISTS costo_agregado   NUMERIC(18,2),
    ADD COLUMN IF NOT EXISTS neto_diferencia  NUMERIC(18,2)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── cuenta_bancaria (tabla creada en V36) ───────────────────────────────────
-- tercero_id      = el banco como tercero (persona jurídica). Ver Fase 1.d.
-- cuenta_contable_id = cuenta del PUC (1110xx). La usa el motor de asientos
--                      para resolver el lado del banco (ver comentario de V66).
ALTER TABLE cuenta_bancaria
    ADD COLUMN IF NOT EXISTS tercero_id         BIGINT,
    ADD COLUMN IF NOT EXISTS cuenta_contable_id BIGINT
MIG_SQL);
    }

    public function down(): void
    {
        // Migración de datos/columnas (ver up). Reversa no automática.
    }
};
