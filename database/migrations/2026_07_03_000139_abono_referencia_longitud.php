<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V139 — `referencia` de los abonos pasa de VARCHAR(100) a VARCHAR(255).
 *
 * Traducción fiel de la migración Flyway V139__abono_referencia_longitud.sql
 * (aura-back-old): cada sentencia de PostgreSQL va en su propio DB::statement.
 * Idempotente (ampliar el tipo es repetible).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- Problema que resuelve: al registrar un movimiento de caja contra una cuenta
-- por cobrar/pagar, el `concepto` del movimiento — VARCHAR(255) y texto libre
-- del cajero — se guarda tal cual en `abonos_cobrar.referencia`, que solo
-- admitía 100. Un concepto largo hacía estallar el INSERT con
-- "value too long for type character varying(100)".
--
-- Se iguala el destino al origen en las dos tablas de abonos (el mismo camino
-- existe para el EGRESO contra cuentas por pagar).

ALTER TABLE abonos_cobrar
    ALTER COLUMN referencia TYPE VARCHAR(255)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE abonos_pagar
    ALTER COLUMN referencia TYPE VARCHAR(255)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN abonos_cobrar.referencia IS
    'Texto libre del recaudo (nº de consignación, concepto del movimiento de caja).'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN abonos_pagar.referencia IS
    'Texto libre del pago (nº de transferencia, concepto del movimiento de caja).'
MIG_SQL);
    }

    public function down(): void
    {
        // Volver a 100 truncaría datos ya guardados. Reversa no automática.
    }
};
