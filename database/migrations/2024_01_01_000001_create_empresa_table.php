<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('empresa')) {
            Schema::create('empresa', function (Blueprint $table) {
                $table->id();
                $table->string('razon_social', 255);
                $table->string('nombre_comercial', 255)->nullable();
                $table->string('nit', 20)->unique();
                $table->char('dv', 1)->nullable();
                $table->text('logo_url')->nullable();
                $table->jsonb('configuracion')->default('{}');
                $table->boolean('activa')->default(true);
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa');
    }
};
