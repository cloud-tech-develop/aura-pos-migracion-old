<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('abonos_cobrar')) {
            Schema::create('abonos_cobrar', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cuenta_cobrar_id')->constrained('cuentas_cobrar');
                $table->foreignId('usuario_id')->constrained('usuario');
                
                $table->decimal('monto', 15, 2);
                $table->string('metodo_pago', 20);
                $table->string('referencia', 100)->nullable();
                
                $table->timestamp('fecha_pago');
                $table->timestamps();
                
                // Índices
                $table->index('cuenta_cobrar_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('abonos_cobrar');
    }
};
