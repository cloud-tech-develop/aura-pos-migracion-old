<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gasto', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('empresa_id')->constrained('empresa');
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursal');
            $table->foreignId('usuario_id')->nullable()->constrained('usuario');
            $table->string('categoria', 100);
            $table->text('descripcion')->nullable();
            $table->decimal('monto', 15, 2)->default(0);
            $table->date('fecha')->default(now());
            $table->boolean('deducible')->default(false);
            $table->string('estado', 20)->default('ACTIVO');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gasto');
    }
};