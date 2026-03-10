<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cuentas_cobrar')) {
            Schema::create('cuentas_cobrar', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresa');
                $table->foreignId('tercero_id')->constrained('tercero');
                $table->foreignId('venta_id')->nullable()->constrained('venta');
                
                $table->string('numero_cuenta', 20)->unique();
                $table->timestamp('fecha_emision');
                $table->timestamp('fecha_vencimiento')->nullable();
                
                $table->decimal('total_deuda', 15, 2);
                $table->decimal('total_abonado', 15, 2)->default(0);
                $table->decimal('saldo_pendiente', 15, 2);
                
                $table->string('estado', 20)->default('activa');
                $table->text('observaciones')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
                
                // Índices
                $table->index('empresa_id');
                $table->index('tercero_id');
                $table->index('estado');
                $table->index('fecha_emision');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_cobrar');
    }
};
