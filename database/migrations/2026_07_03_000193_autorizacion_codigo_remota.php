<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V193 — Autorización del supervisor con código de un solo uso o aprobación remota.
 *
 * Traducción de la migración Flyway V193__autorizacion_codigo_remota.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 *
 * La clave del supervisor ya no se escribe en el equipo del cajero: código de
 * 6 dígitos generado en su sesión (solo se guarda su huella) o solicitud que
 * aprueba desde su sesión. Columnas nuevas en autorizacion.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE autorizacion ALTER COLUMN solicitante_id DROP NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE autorizacion ALTER COLUMN autorizador_id DROP NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE autorizacion ADD COLUMN IF NOT EXISTS detalle TEXT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE autorizacion ADD COLUMN IF NOT EXISTS codigo_hash VARCHAR(64)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_autorizacion_estado ON autorizacion (empresa_id, estado, expira_en)
MIG_SQL);
    }

    public function down(): void
    {
        // Sin reversa: los cambios son aditivos.
    }
};
