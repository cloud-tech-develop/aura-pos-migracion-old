<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuenta_bancaria', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->string('nombre', 200);
            $table->string('tipo', 30); // BANCO | CAJA | NEQUI | DAVIPLATA | OTROS
            $table->string('banco', 200)->nullable();
            $table->string('numero_cuenta', 100)->nullable();
            $table->string('titular', 300)->nullable();
            $table->decimal('saldo_inicial', 15, 2)->default(0);
            $table->decimal('saldo_actual', 15, 2)->default(0);
            $table->boolean('activa')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('empresa_id')->references('id')->on('empresa');

            $table->index('empresa_id', 'idx_cuenta_bancaria_empresa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuenta_bancaria');
    }
};