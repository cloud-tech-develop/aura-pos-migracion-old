<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V136 — notas crédito/débito electrónicas (Factus v1).
 *
 * Traducción fiel de la migración Flyway V136__nota_electronica.sql (aura-back-old):
 * cada sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- Documento de venta que referencia una factura electrónica (bill_id) para
-- anularla/corregirla ante la DIAN. Se persiste igual que la nómina electrónica:
-- reference_code + CUDE + estado, para guarda anti-reenvío, listar y XML.

CREATE TABLE IF NOT EXISTS nota_electronica (
    id                      BIGSERIAL PRIMARY KEY,
    empresa_id              INTEGER      NOT NULL,
    tipo                    VARCHAR(10)  NOT NULL,   -- CREDITO | DEBITO
    reference_code          VARCHAR(100) NOT NULL,
    bill_id                 BIGINT,                  -- factura referenciada (null si sin referencia)
    numero                  VARCHAR(30),             -- número asignado por Factus (p.ej. NC76)
    cude                    VARCHAR(120),
    estado                  VARCHAR(20)  NOT NULL DEFAULT 'PENDIENTE',
    customization_id        INTEGER,
    correction_concept_code INTEGER,
    total                   NUMERIC(15,2),
    payload_json            JSONB,
    response_json           JSONB,
    xml                     TEXT,
    created_at              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_nota_tipo   CHECK (tipo IN ('CREDITO', 'DEBITO')),
    CONSTRAINT chk_nota_estado CHECK (estado IN ('PENDIENTE', 'ENVIADO', 'ACEPTADO', 'RECHAZADO', 'ANULADO')),
    CONSTRAINT uq_nota_ref     UNIQUE (empresa_id, reference_code)
)
MIG_SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_nota_empresa ON nota_electronica (empresa_id)');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS nota_electronica');
    }
};
