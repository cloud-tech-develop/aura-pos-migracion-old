<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('permiso_empresa')) {
            Schema::table('permiso_empresa', function (Blueprint $table) {
                $table->dropForeign(['submodulo_id']);
                $table->dropForeign(['usuario_id']);
                $table->dropForeign(['empresa_id']);
            });
            Schema::dropIfExists('permiso_empresa');
        }
    }

    public function down(): void
    {
        Schema::create('permiso_empresa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa')->onDelete('cascade');
            $table->foreignId('submodulo_id')->constrained('submodulos')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['empresa_id', 'submodulo_id', 'usuario_id']);
        });
    }
};
