<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_submodulo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa')->onDelete('cascade');
            $table->foreignId('submodulo_id')->constrained('submodulos')->onDelete('cascade');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['empresa_id', 'submodulo_id']);
            $table->index('empresa_id');
            $table->index('submodulo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_submodulo');
    }
};