<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V120 — tercero codigo seguridad social.
 *
 * Traducción fiel de la migración Flyway V120__tercero_codigo_seguridad_social.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V120: código oficial UGPP para terceros que son EPS/AFP/CCF/ARL ─────────
--
-- Las entidades de seguridad social se crean como terceros con su rol
-- (EPS/AFP/CCF/ARL) — no en un catálogo aparte. Pero PILA exige el código
-- oficial UGPP de cada entidad: sin él, el operador rechaza el archivo.
--
-- Ese código no es el NIT ni un texto libre cualquiera: es el identificador
-- nacional de la entidad. Vive aquí, en el tercero que se paga.

ALTER TABLE tercero
    ADD COLUMN IF NOT EXISTS codigo_seguridad_social VARCHAR(20)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN tercero.codigo_seguridad_social IS
    'Código oficial UGPP cuando el tercero es EPS/AFP/CCF/ARL. Lo exige PILA. '
    'Null para el resto de terceros.'
MIG_SQL);
    }

    public function down(): void
    {
        // Migración de datos/columnas (ver up). Reversa no automática.
    }
};
