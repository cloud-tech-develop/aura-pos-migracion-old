<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('historial_sesion')) {
            Schema::create('historial_sesion', function (Blueprint $table) {
                $table->id();
                $table->foreignId('usuario_id')->nullable()->constrained('usuario');
                $table->foreignId('sucursal_id')->nullable()->constrained('sucursal');
                $table->string('token_jti', 255)->nullable();
                $table->string('ip_address', 50)->nullable();
                $table->string('dispositivo', 100)->nullable();
                $table->timestamp('fecha_inicio')->useCurrent();
                $table->timestamp('fecha_fin')->nullable();
                $table->boolean('activo')->default(true);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_sesion');
    }
};
