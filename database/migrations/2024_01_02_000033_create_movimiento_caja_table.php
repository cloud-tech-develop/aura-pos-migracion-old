<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimiento_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_caja_id')
                  ->constrained('turno_caja')
                  ->cascadeOnDelete();
            $table->foreignId('usuario_id')
                  ->constrained('usuario')
                  ->cascadeOnDelete();
            $table->enum('tipo', ['INGRESO', 'EGRESO']);
            $table->string('concepto', 200);
            $table->decimal('monto', 18, 2)->check('monto > 0');
            $table->timestamp('fecha')->useCurrent();

            $table->index('turno_caja_id', 'idx_movimiento_caja_turno');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimiento_caja');
    }
};