<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V204 — A quién se le paga cada impuesto (tercero acreedor en impuesto y tarifa_retencion, DIAN por empresa) y el pago de la declaración.
 *
 * Traducción de la migración Flyway V204__impuestos_tercero_acreedor_pago_declaracion.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE impuesto ADD COLUMN IF NOT EXISTS tercero_acreedor_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE tarifa_retencion ADD COLUMN IF NOT EXISTS tercero_acreedor_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO tercero (empresa_id, tipo_documento, numero_documento, dv, razon_social,
                     tipo_persona, es_cliente, es_proveedor, activo, municipio,
                     created_at, updated_at)
SELECT e.id, 'NIT', '800197268', '4', 'DIRECCIÓN DE IMPUESTOS Y ADUANAS NACIONALES - DIAN',
       'JURIDICA', false, true, true, 'Bogotá, D.C.', now(), now()
  FROM empresa e
 WHERE NOT EXISTS (SELECT 1 FROM tercero t
                    WHERE t.empresa_id = e.id AND t.numero_documento = '800197268')
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE impuesto i
   SET tercero_acreedor_id = t.id
  FROM tercero t
 WHERE t.empresa_id = i.empresa_id
   AND t.numero_documento = '800197268'
   AND t.deleted_at IS NULL
   AND i.tipo IN ('IVA', 'INC')
   AND i.tercero_acreedor_id IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE tarifa_retencion r
   SET tercero_acreedor_id = t.id
  FROM tercero t
 WHERE t.empresa_id = r.empresa_id
   AND t.numero_documento = '800197268'
   AND t.deleted_at IS NULL
   AND r.tipo IN ('RETEFUENTE', 'RETEIVA')
   AND r.tercero_acreedor_id IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS declaracion_pago (
    id                   BIGSERIAL      PRIMARY KEY,
    empresa_id           INT            NOT NULL REFERENCES empresa(id),
    consecutivo          INT            NOT NULL,
    tipo                 VARCHAR(12)    NOT NULL,
    desde                DATE           NOT NULL,
    hasta                DATE           NOT NULL,
    tercero_acreedor_id  BIGINT         NOT NULL REFERENCES tercero(id),
    fecha                DATE           NOT NULL,
    numero_formulario    VARCHAR(30),
    valor                NUMERIC(18,2)  NOT NULL,
    metodo_pago          VARCHAR(20)    NOT NULL,
    cuenta_bancaria_id   BIGINT,
    cuenta_contable_id   BIGINT,
    estado               VARCHAR(10)    NOT NULL DEFAULT 'PAGADA',
    usuario_id           INT,
    created_at           TIMESTAMP      NOT NULL DEFAULT now(),
    anulado_por          INT,
    anulado_at           TIMESTAMP,
    motivo_anulacion     VARCHAR(300)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_declaracion_pago_periodo
    ON declaracion_pago (empresa_id, tipo, desde, hasta)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_declaracion_pago_vigente
    ON declaracion_pago (empresa_id, tipo, desde, hasta, tercero_acreedor_id)
    WHERE estado = 'PAGADA'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS declaracion_pago_cuenta (
    id         BIGSERIAL      PRIMARY KEY,
    pago_id    BIGINT         NOT NULL REFERENCES declaracion_pago(id) ON DELETE CASCADE,
    cuenta_id  BIGINT         NOT NULL REFERENCES plan_cuenta(id),
    valor      NUMERIC(18,2)  NOT NULL
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_declaracion_pago_cuenta_pago
    ON declaracion_pago_cuenta (pago_id)
MIG_SQL);
    }

    public function down(): void
    {
        // Aditiva: no se revierte.
    }
};
