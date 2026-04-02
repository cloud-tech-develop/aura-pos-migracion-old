<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE movimiento_inventario ALTER COLUMN tipo_movimiento TYPE VARCHAR(50)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE movimiento_inventario ALTER COLUMN tipo_movimiento TYPE VARCHAR(20)');
    }
};