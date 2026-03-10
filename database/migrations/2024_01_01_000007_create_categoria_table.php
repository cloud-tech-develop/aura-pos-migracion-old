<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('categoria')) {
            Schema::create('categoria', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->nullable()->constrained('empresa');
                $table->string('nombre', 150);
                $table->unsignedBigInteger('padre_id')->nullable();
                $table->decimal('impuesto_defecto', 5, 2)->default(0);
                $table->boolean('activo')->default(true);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->timestamp('deleted_at')->nullable();

                $table->foreign('padre_id')->references('id')->on('categoria');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('categoria');
    }
};
