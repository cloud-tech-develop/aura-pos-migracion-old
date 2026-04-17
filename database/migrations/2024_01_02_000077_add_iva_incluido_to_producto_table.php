<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('producto', 'iva_incluido')) {
            Schema::table('producto', function (Blueprint $table) {
                $table->boolean('iva_incluido')->default(false)->after('iva_porcentaje');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('producto', 'iva_incluido')) {
            Schema::table('producto', function (Blueprint $table) {
                $table->dropColumn('iva_incluido');
            });
        }
    }
};