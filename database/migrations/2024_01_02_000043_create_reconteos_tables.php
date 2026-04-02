<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconteos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('empresa_id')->constrained('empresa');
            $table->foreignId('sucursal_id')->constrained('sucursal');
            $table->string('estado', 30)->default('BORRADOR');
            $table->string('tipo', 20)->default('TOTAL');
            $table->string('observaciones', 500)->nullable();
            $table->unsignedInteger('creado_por_id')->nullable();
            $table->unsignedInteger('aprobado_por_id')->nullable();
            $table->timestamp('fecha_inicio')->useCurrent();
            $table->timestamp('fecha_cierre')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('creado_por_id')->references('id')->on('usuario');
            $table->foreign('aprobado_por_id')->references('id')->on('usuario');

            $table->index('empresa_id', 'idx_reconteos_empresa');
            $table->index(['sucursal_id', 'empresa_id'], 'idx_reconteos_sucursal');
            $table->index(['estado', 'empresa_id'], 'idx_reconteos_estado');
        });

        DB::statement("ALTER TABLE reconteos ADD CONSTRAINT chk_reconteo_estado CHECK (estado IN ('BORRADOR', 'EN_CONTEO', 'APROBADO', 'ANULADO'))");
        DB::statement("ALTER TABLE reconteos ADD CONSTRAINT chk_reconteo_tipo CHECK (tipo IN ('TOTAL', 'PARCIAL'))");

        Schema::create('reconteo_detalles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('reconteo_id');
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('lote_id')->nullable();
            $table->decimal('stock_sistema', 15, 4)->default(0);
            $table->decimal('stock_contado', 15, 4)->nullable();
            $table->boolean('ajuste_aplicado')->default(false);

            $table->foreign('reconteo_id')->references('id')->on('reconteos')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('producto');
            $table->foreign('lote_id')->references('id')->on('lote');

            $table->index('reconteo_id', 'idx_reconteo_detalles_reconteo');
            $table->index(['producto_id', 'reconteo_id'], 'idx_reconteo_detalles_producto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconteo_detalles');
        Schema::dropIfExists('reconteos');
    }
};