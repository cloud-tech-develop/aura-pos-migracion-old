<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V173 — Notas contables (comprobante de diario CD) con ciclo borrador.
 *
 * La nota nace en BORRADOR sin consecutivo (editable y borrable), el CD-######
 * se asigna al contabilizar y la anulación exige motivo y deja traza. No hay
 * tabla nueva: la nota es un asiento_contable MANUAL con tipo_comprobante 'CD';
 * solo se agregan los campos de auditoría.
 *
 * Traducción de la migración Flyway V173__notas_contables.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE asiento_contable ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE asiento_contable ADD COLUMN IF NOT EXISTS contabilizado_por INTEGER
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE asiento_contable ADD COLUMN IF NOT EXISTS contabilizado_at TIMESTAMP
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE asiento_contable ADD COLUMN IF NOT EXISTS anulado_por INTEGER
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE asiento_contable ADD COLUMN IF NOT EXISTS anulado_at TIMESTAMP
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE asiento_contable ADD COLUMN IF NOT EXISTS motivo_anulacion VARCHAR(300)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE asiento_contable
   SET tipo_comprobante = 'CD'
 WHERE tipo_origen = 'MANUAL'
   AND tipo_comprobante IS NULL
   AND numero_comprobante LIKE 'CD-%'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS ix_asiento_notas_diario
    ON asiento_contable (empresa_id, estado, fecha)
    WHERE tipo_origen = 'MANUAL' AND tipo_comprobante = 'CD'
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ix_asiento_notas_diario');
        DB::statement('ALTER TABLE asiento_contable DROP COLUMN IF EXISTS motivo_anulacion');
        DB::statement('ALTER TABLE asiento_contable DROP COLUMN IF EXISTS anulado_at');
        DB::statement('ALTER TABLE asiento_contable DROP COLUMN IF EXISTS anulado_por');
        DB::statement('ALTER TABLE asiento_contable DROP COLUMN IF EXISTS contabilizado_at');
        DB::statement('ALTER TABLE asiento_contable DROP COLUMN IF EXISTS contabilizado_por');
        DB::statement('ALTER TABLE asiento_contable DROP COLUMN IF EXISTS updated_at');
    }
};
