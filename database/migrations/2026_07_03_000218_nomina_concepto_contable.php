<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V218 — Cuenta y tercero por concepto de nómina (rediseño R4c-2).
 *
 * Traducción de la migración Flyway V218__nomina_concepto_contable.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nomina_concepto_contable (
    id                 BIGSERIAL    PRIMARY KEY,
    empresa_id         INTEGER      NOT NULL REFERENCES empresa(id),
    concepto_codigo    VARCHAR(30)  NOT NULL,
    cuenta_debito_id   BIGINT       REFERENCES plan_cuenta(id),
    cuenta_credito_id  BIGINT       REFERENCES plan_cuenta(id),
    tercero_credito    VARCHAR(20),
    tercero_fijo_id    BIGINT       REFERENCES tercero(id),
    created_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP,
    CONSTRAINT ck_nomina_concepto_contable_tercero CHECK (tercero_credito IS NULL OR tercero_credito IN
        ('EMPLEADO', 'EPS', 'AFP', 'ARL', 'CCF', 'DIAN', 'NINGUNO', 'FIJO')),
    CONSTRAINT ck_nomina_concepto_contable_fijo CHECK (tercero_credito IS DISTINCT FROM 'FIJO'
        OR tercero_fijo_id IS NOT NULL)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_nomina_concepto_contable
    ON nomina_concepto_contable (empresa_id, concepto_codigo)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS nomina_concepto_contable');
    }
};
