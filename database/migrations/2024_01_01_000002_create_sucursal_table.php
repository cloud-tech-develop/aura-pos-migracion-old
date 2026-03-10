<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sucursal')) {
            Schema::create('sucursal', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->nullable()->constrained('empresa');
                $table->string('codigo', 20)->nullable();
                $table->string('nombre', 150);
                $table->string('direccion', 255)->nullable();
                $table->string('ciudad', 100)->nullable();
                $table->string('telefono', 50)->nullable();
                $table->string('prefijo_facturacion', 10)->nullable();
                $table->unsignedBigInteger('consecutivo_actual')->default(1);
                $table->boolean('activa')->default(true);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursal');
    }
};
