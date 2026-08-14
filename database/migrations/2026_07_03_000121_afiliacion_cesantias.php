<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V121 — afiliacion cesantias.
 *
 * Traducción fiel de la migración Flyway V121__afiliacion_cesantias.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V121: agregar CESANTIAS como rol y tipo de afiliación ───────────────────
--
-- El fondo de cesantías es otra entidad a la que el empleado está afiliado (y a
-- la que se le paga). Se modela igual que EPS/AFP/CCF/ARL: un tercero con rol
-- CESANTIAS, y una afiliación del contrato de tipo CESANTIAS.

ALTER TABLE tercero_rol DROP CONSTRAINT IF EXISTS chk_tercero_rol
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE tercero_rol ADD CONSTRAINT chk_tercero_rol CHECK (
    rol IN ('CLIENTE','PROVEEDOR','EMPLEADO','BANCO','EPS','AFP','CCF','ARL','CESANTIAS')
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_afiliacion DROP CONSTRAINT IF EXISTS chk_afil_tipo
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_afiliacion ADD CONSTRAINT chk_afil_tipo CHECK (
    tipo IN ('EPS','AFP','CCF','ARL','CESANTIAS')
)
MIG_SQL);
    }

    public function down(): void
    {
        // Migración de datos/columnas (ver up). Reversa no automática.
    }
};
