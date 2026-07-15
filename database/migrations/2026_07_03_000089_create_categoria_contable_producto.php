<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V89 · E4 · Categorías contables de producto (C2): bebidas gravadas, servicios
 * y activos contabilizan a cuentas distintas sin que el cajero vea cuentas.
 * Jerarquía de resolución: override del producto → categoría → concepto empresa.
 * Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('categoria_contable_producto')) {
            Schema::create('categoria_contable_producto', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->string('nombre', 80);
                $table->string('tipo', 20)->default('BIEN');   // BIEN|SERVICIO|INSUMO|ACTIVO_FIJO
                $table->unsignedBigInteger('cuenta_ingreso_id')->nullable();
                $table->unsignedBigInteger('cuenta_inventario_id')->nullable();
                $table->unsignedBigInteger('cuenta_costo_id')->nullable();
                $table->unsignedBigInteger('cuenta_devolucion_id')->nullable();
                $table->unsignedBigInteger('impuesto_id')->nullable();  // FK a impuesto (E5), aún sin usar
                $table->boolean('activo')->default(true);
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['empresa_id', 'nombre']);
                $table->index(['empresa_id', 'activo'], 'idx_categoria_contable_empresa');
            });
        }

        // Producto: categoría + overrides excepcionales (normalmente NULL).
        DB::statement('ALTER TABLE producto ADD COLUMN IF NOT EXISTS categoria_contable_id BIGINT');
        DB::statement('ALTER TABLE producto ADD COLUMN IF NOT EXISTS cuenta_ingreso_id BIGINT');
        DB::statement('ALTER TABLE producto ADD COLUMN IF NOT EXISTS cuenta_costo_id BIGINT');
        DB::statement('ALTER TABLE producto ADD COLUMN IF NOT EXISTS cuenta_inventario_id BIGINT');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE producto DROP COLUMN IF EXISTS categoria_contable_id');
        DB::statement('ALTER TABLE producto DROP COLUMN IF EXISTS cuenta_ingreso_id');
        DB::statement('ALTER TABLE producto DROP COLUMN IF EXISTS cuenta_costo_id');
        DB::statement('ALTER TABLE producto DROP COLUMN IF EXISTS cuenta_inventario_id');
        Schema::dropIfExists('categoria_contable_producto');
    }
};
