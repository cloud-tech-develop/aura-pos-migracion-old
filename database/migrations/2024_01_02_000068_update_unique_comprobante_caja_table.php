<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobante_caja', function (Blueprint $table) {
            $table->dropUnique('comprobante_caja_numero_comprobante_unique');
            $table->unique(['empresa_id', 'numero_comprobante'], 'comprobante_empresa_numero_unique');
        });
    }

    public function down(): void
    {
        Schema::table('comprobante_caja', function (Blueprint $table) {
            $table->dropUnique('comprobante_empresa_numero_unique');
            $table->unique('numero_comprobante', 'comprobante_caja_numero_comprobante_unique');
        });
    }
};