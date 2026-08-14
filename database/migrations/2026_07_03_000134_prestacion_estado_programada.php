<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V134 — prestaciones: estado PROGRAMADA en el ciclo de pago (B-07).
 *
 * Traducción fiel de la migración Flyway V134__prestacion_estado_programada.sql
 * (aura-back-old): cada sentencia de PostgreSQL va en su propio DB::statement.
 * Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- Antes el pago saltaba APROBADA → PAGADA en un solo paso, descontando el banco
-- y contabilizando de una, sin distinguir entre "orden de pago generada" y
-- "el banco confirmó el pago". Una transferencia rechazada dejaba la prestación
-- marcada como pagada y el asiento ya hecho.
--
-- El nuevo estado PROGRAMADA separa la dispersión de la confirmación: la
-- transferencia queda PROGRAMADA y solo pasa a PAGADA cuando se confirma
-- (ahí se descuenta el banco y se contabiliza). El efectivo/cheque siguen
-- yendo directo a PAGADA.
--
-- Seguro: solo amplía el CHECK con un valor nuevo; ninguna fila existente lo usa.

ALTER TABLE liquidacion_prestacion
    DROP CONSTRAINT IF EXISTS chk_prestacion_estado
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE liquidacion_prestacion
    ADD CONSTRAINT chk_prestacion_estado
    CHECK (estado IN ('BORRADOR', 'APROBADA', 'PROGRAMADA', 'PAGADA', 'ANULADA'))
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE liquidacion_prestacion DROP CONSTRAINT IF EXISTS chk_prestacion_estado');

        DB::statement(<<<'MIG_SQL'
ALTER TABLE liquidacion_prestacion
    ADD CONSTRAINT chk_prestacion_estado
    CHECK (estado IN ('BORRADOR', 'APROBADA', 'PAGADA', 'ANULADA'))
MIG_SQL);
    }
};
