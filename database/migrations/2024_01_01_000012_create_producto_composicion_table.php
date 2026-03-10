<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('producto_composicion')) {
            Schema::create('producto_composicion', function (Blueprint $table) {
                $table->id();
                $table->foreignId('producto_padre_id')->nullable()->constrained('producto');
                $table->foreignId('producto_hijo_id')->nullable()->constrained('producto');
                $table->decimal('cantidad', 14, 4)->nullable();
                $table->string('tipo', 20)->nullable(); // KIT, RECETA
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_composicion');
    }
};
