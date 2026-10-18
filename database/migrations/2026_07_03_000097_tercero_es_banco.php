<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * tercero.es_banco: la creó Hibernate en aura-back-old y ninguna migración la
 * agregaba. 000098 (tercero_rol) la lee para pasar los bancos a tercero_rol; sin
 * ella una base nueva falla ahí. Idempotente: en bases existentes no hace nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tercero ADD COLUMN IF NOT EXISTS es_banco BOOLEAN');
    }

    public function down(): void
    {
        // Sin reversa: 000098 y siguientes dependen de la columna.
    }
};
