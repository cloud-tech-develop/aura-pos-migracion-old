<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_pago', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('compra_id');
            $table->unsignedInteger('usuario_id')->nullable();
            $table->string('metodo_pago', 30);
            $table->decimal('monto', 15, 2);
            $table->string('banco', 300)->nullable();
            $table->timestamp('fecha_pago')->useCurrent();
            $table->boolean('activo')->default(true);

            $table->foreign('compra_id')->references('id')->on('compra');
            $table->foreign('usuario_id')->references('id')->on('usuario');

            $table->index('compra_id', 'idx_compra_pago_compra');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_pago');
    }
};