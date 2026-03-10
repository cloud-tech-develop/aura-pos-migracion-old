<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('usuario_sucursal')) {
            Schema::create('usuario_sucursal', function (Blueprint $table) {
                $table->id();
                $table->foreignId('usuario_id')->nullable()->constrained('usuario');
                $table->foreignId('sucursal_id')->nullable()->constrained('sucursal');
                $table->boolean('es_default')->default(false);
                $table->boolean('activo')->default(true);

                $table->unique(['usuario_id', 'sucursal_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_sucursal');
    }
};
