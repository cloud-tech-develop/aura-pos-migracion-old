<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V176 — Devuelve a CONTABILIZADO los asientos originales que una anulación dejó
 * ANULADOS (bug 2026-08-15: el reporte mostraba solo la reversa).
 *
 * Traducción de la migración Flyway V176__restaurar_asientos_reversados.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
UPDATE asiento_contable o
   SET estado = 'CONTABILIZADO'
 WHERE o.estado = 'ANULADO'
   AND o.numero_comprobante IS NOT NULL
   AND o.origen_id IS NOT NULL
   AND EXISTS (SELECT 1
                 FROM asiento_contable r
                WHERE r.empresa_id  = o.empresa_id
                  AND r.tipo_origen = 'ANULACION_' || o.tipo_origen
                  AND r.origen_id   = o.origen_id
                  AND r.estado     <> 'ANULADO'
                  AND r.descripcion LIKE '%(reversa de ' || o.numero_comprobante || ')%')
MIG_SQL);
    }

    public function down(): void
    {
        // Irreversible a propósito: volver a ANULAR los originales reintroduce el bug.
    }
};
