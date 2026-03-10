<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('usuario')) {
            Schema::create('usuario', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->nullable()->constrained('empresa');
                $table->foreignId('tercero_id')->nullable()->constrained('tercero');
                $table->string('username', 100)->unique();
                $table->string('password_hash', 255);
                $table->string('pin_acceso_rapido', 255)->nullable();
                $table->string('rol', 50)->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};
