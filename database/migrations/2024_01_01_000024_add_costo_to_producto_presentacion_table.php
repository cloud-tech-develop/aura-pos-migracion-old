<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('producto_presentacion', 'costo')) {
            Schema::table('producto_presentacion', function (Blueprint $table) {
                $table->decimal('costo', 10, 2)->nullable()->after('precio');
            });
        }
    }

    public function down(): void
    {
        Schema::table('producto_presentacion', function (Blueprint $table) {
            $table->dropColumn('costo');
        });
    }
};