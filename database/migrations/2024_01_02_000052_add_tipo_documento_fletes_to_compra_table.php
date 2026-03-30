<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compra', function (Blueprint $table) {
            $table->string('tipo_documento', 30)->default('FACTURA_COMPRA')->after('estado');
            $table->decimal('fletes', 15, 2)->default(0)->after('tipo_documento');
        });
    }

    public function down(): void
    {
        Schema::table('compra', function (Blueprint $table) {
            $table->dropColumn(['tipo_documento', 'fletes']);
        });
    }
};