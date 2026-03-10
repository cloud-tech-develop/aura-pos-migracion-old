<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('metodo', 10);
            $table->string('endpoint', 500);
            $table->integer('status_code');
            $table->string('categoria', 10); // info, warn, danger
            $table->text('mensaje')->nullable();
            $table->text('detalle')->nullable();
            $table->string('grupo_hash', 64);
            $table->foreignId('empresa_id')->nullable()->constrained('empresa')->nullOnDelete();
            $table->string('usuario_nombre', 200)->nullable();
            $table->string('ip_origen', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Índices
            $table->index('categoria',   'idx_error_log_categoria');
            $table->index('grupo_hash',  'idx_error_log_grupo_hash');
            $table->index('empresa_id',  'idx_error_log_empresa_id');
            $table->index('created_at',  'idx_error_log_created_at');
            $table->index('status_code', 'idx_error_log_status_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_log');
    }
};