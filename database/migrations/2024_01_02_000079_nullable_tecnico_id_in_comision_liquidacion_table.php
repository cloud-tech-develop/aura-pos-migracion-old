<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Solo ejecuta si tecnico_id aún es NOT NULL
        $isNullable = DB::selectOne("
            SELECT is_nullable
            FROM information_schema.columns
            WHERE table_name = 'comision_liquidacion'
              AND column_name = 'tecnico_id'
        ");

        if ($isNullable && $isNullable->is_nullable === 'NO') {
            DB::statement('ALTER TABLE comision_liquidacion ALTER COLUMN tecnico_id DROP NOT NULL');
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE comision_liquidacion ALTER COLUMN tecnico_id SET NOT NULL');
    }
};