<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V198 — Acción especial "Editar datos de la empresa" (caja.usuarios:EDITAR_EMPRESA).
 *
 * Traducción de la migración Flyway V198__permiso_editar_empresa.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
INSERT INTO permiso_accion_especial (submodulo_id, codigo, nombre, descripcion, hereda_de, orden)
SELECT s.id, 'EDITAR_EMPRESA', 'Editar datos de la empresa',
       'Cambiar nombre comercial, logo, contacto, resolución de facturación y rangos de numeración',
       NULL, 2
FROM modulos m
JOIN submodulos s ON s.modulo_id = m.id AND s.codigo = 'usuarios'
WHERE m.codigo = 'caja'
ON CONFLICT (submodulo_id, codigo) DO NOTHING
MIG_SQL);
    }

    public function down(): void
    {
        // Sin vuelta atrás automática.
    }
};
