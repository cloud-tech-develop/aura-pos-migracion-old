<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V169 — control de crédito completo.
 * 
 * Reglas de crédito con descripción y días entre aplicaciones (para que un aumento
 * de cupo no se acumule en cada pago); el historial guarda qué regla actuó.
 * Solicitudes de autorización de cupo con quién la pidió, vigencia de la
 * aprobación y cuándo la consumió una venta (estados USADA y VENCIDA).
 *
 * Traducción de la migración Flyway V169__control_credito.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE regla_credito ADD COLUMN IF NOT EXISTS descripcion VARCHAR(300)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE regla_credito ADD COLUMN IF NOT EXISTS dias_entre_aplicaciones INTEGER NOT NULL DEFAULT 30
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE regla_credito ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE historial_credito ADD COLUMN IF NOT EXISTS regla_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_historial_credito_regla
    ON historial_credito (regla_id, tercero_id, created_at DESC)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE solicitud_autorizacion_credito ADD COLUMN IF NOT EXISTS solicitado_por_id INTEGER
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE solicitud_autorizacion_credito ADD COLUMN IF NOT EXISTS observacion VARCHAR(300)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE solicitud_autorizacion_credito ADD COLUMN IF NOT EXISTS respondido_at TIMESTAMP
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE solicitud_autorizacion_credito ADD COLUMN IF NOT EXISTS vigente_hasta TIMESTAMP
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE solicitud_autorizacion_credito ADD COLUMN IF NOT EXISTS usada_at TIMESTAMP
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_solicitud_credito_estado') THEN
        ALTER TABLE solicitud_autorizacion_credito ADD CONSTRAINT ck_solicitud_credito_estado
            CHECK (estado IN ('PENDIENTE','APROBADA','RECHAZADA','USADA','VENCIDA'));
    END IF;
END $$
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_solicitud_credito_estado
    ON solicitud_autorizacion_credito (empresa_id, estado, created_at DESC)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_solicitud_credito_estado');
        DB::statement('ALTER TABLE solicitud_autorizacion_credito DROP CONSTRAINT IF EXISTS ck_solicitud_credito_estado');
        foreach (['usada_at', 'vigente_hasta', 'respondido_at', 'observacion', 'solicitado_por_id'] as $c) {
            DB::statement("ALTER TABLE solicitud_autorizacion_credito DROP COLUMN IF EXISTS $c");
        }
        DB::statement('DROP INDEX IF EXISTS idx_historial_credito_regla');
        DB::statement('ALTER TABLE historial_credito DROP COLUMN IF EXISTS regla_id');
        DB::statement('ALTER TABLE regla_credito DROP COLUMN IF EXISTS updated_at');
        DB::statement('ALTER TABLE regla_credito DROP COLUMN IF EXISTS dias_entre_aplicaciones');
        DB::statement('ALTER TABLE regla_credito DROP COLUMN IF EXISTS descripcion');
    }
};
