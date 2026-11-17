<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V248 — Nombres de los documentos adicionales adjuntos a un correo (los archivos no se guardan).
 *
 * Traducción de la migración Flyway V248__correo_adjuntos_adicionales.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE correo_envio ADD COLUMN IF NOT EXISTS adjuntos_adicionales VARCHAR(1000)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE correo_envio DROP COLUMN IF EXISTS adjuntos_adicionales');
    }
};
