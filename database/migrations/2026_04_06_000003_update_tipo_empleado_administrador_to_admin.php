<?php

use Illuminate\Database\Migrations\Migration;

use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('tipos_empleado')->where('nombre', 'ADMINISTRADOR')->update(['nombre' => 'ADMIN']);
    }

    public function down(): void
    {
        DB::table('tipos_empleado')->where('nombre', 'ADMIN')->update(['nombre' => 'ADMINISTRADOR']);
    }
};
