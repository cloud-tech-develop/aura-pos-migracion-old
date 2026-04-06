<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modulos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 500)->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('modulos')->insert([
            ['id' => 1, 'nombre' => 'Principal', 'codigo' => 'principal', 'descripcion' => 'Dashboard y Punto de Venta', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nombre' => 'Catálogo', 'codigo' => 'catalogo', 'descripcion' => 'Gestión de productos, categorías y marcas', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nombre' => 'Precios', 'codigo' => 'precios', 'descripcion' => 'Listas de precio y descuentos', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'nombre' => 'Inventario', 'codigo' => 'inventario', 'descripcion' => 'Control de inventario y stock', 'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'nombre' => 'Compras', 'codigo' => 'compras', 'descripcion' => 'Gestión de compras', 'orden' => 5, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'nombre' => 'Ventas', 'codigo' => 'ventas', 'descripcion' => 'Gestión de ventas', 'orden' => 6, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'nombre' => 'Cuentas', 'codigo' => 'cuentas', 'descripcion' => 'Cuentas por cobrar y pagar', 'orden' => 7, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'nombre' => 'Cartera', 'codigo' => 'cartera', 'descripcion' => 'Gestión de cartera', 'orden' => 8, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'nombre' => 'Tesorería', 'codigo' => 'tesoreria', 'descripcion' => 'Gestión de tesorería', 'orden' => 9, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'nombre' => 'Contabilidad', 'codigo' => 'contabilidad', 'descripcion' => 'Gestión contable', 'orden' => 10, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'nombre' => 'Recursos Humanos', 'codigo' => 'recursos-humanos', 'descripcion' => 'Gestión de empleados y nómina', 'orden' => 11, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'nombre' => 'Reportes', 'codigo' => 'reportes', 'descripcion' => 'Reportes de ventas e inventario', 'orden' => 12, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'nombre' => 'Terceros y Sucursales', 'codigo' => 'terceros-y-sucursales', 'descripcion' => 'Terceros y Sucursales', 'orden' => 13, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 14, 'nombre' => 'Vendedores', 'codigo' => 'vendedores', 'descripcion' => 'Gestion de vendedores', 'orden' => 14, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('modulos')->delete();
        Schema::dropIfExists('modulos');
    }
};
