<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('unidad_medida')) {
            Schema::create('unidad_medida', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 50);
                $table->string('abreviatura', 10);
                $table->boolean('permite_decimales')->default(false);
                $table->boolean('activo')->default(true);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->timestamp('deleted_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unidad_medida');
    }
};
