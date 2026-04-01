<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('submodulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modulo_id')->constrained('modulos')->onDelete('cascade');
            $table->string('nombre', 100);
            $table->string('codigo', 50);
            $table->string('descripcion', 500)->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['modulo_id', 'codigo']);
        });

        $submodulos = [
            // Principal (modulo_id = 1)
            ['modulo_id' => 1, 'nombre' => 'Dashboard', 'codigo' => 'dashboard', 'descripcion' => 'Panel de control', 'orden' => 1],
            ['modulo_id' => 1, 'nombre' => 'Punto de Venta', 'codigo' => 'pos', 'descripcion' => 'Punto de venta', 'orden' => 2],

            // Catálogo (modulo_id = 2)
            ['modulo_id' => 2, 'nombre' => 'Productos', 'codigo' => 'productos', 'descripcion' => 'Gestión de productos', 'orden' => 1],
            ['modulo_id' => 2, 'nombre' => 'Categorías', 'codigo' => 'categorias', 'descripcion' => 'Categorías de productos', 'orden' => 2],
            ['modulo_id' => 2, 'nombre' => 'Marcas', 'codigo' => 'marcas', 'descripcion' => 'Marcas de productos', 'orden' => 3],
            ['modulo_id' => 2, 'nombre' => 'Unidades', 'codigo' => 'unidades', 'descripcion' => 'Unidades de medida', 'orden' => 4],
            ['modulo_id' => 2, 'nombre' => 'Presentaciones', 'codigo' => 'presentaciones', 'descripcion' => 'Presentaciones de productos', 'orden' => 5],
            ['modulo_id' => 2, 'nombre' => 'Composiciones', 'codigo' => 'composiciones', 'descripcion' => 'Composiciones de productos', 'orden' => 6],
            ['modulo_id' => 2, 'nombre' => 'Etiquetas', 'codigo' => 'etiquetas', 'descripcion' => 'Etiquetas de productos', 'orden' => 7],

            // Precios (modulo_id = 3)
            ['modulo_id' => 3, 'nombre' => 'Listas de Precio', 'codigo' => 'listas-de-precio', 'descripcion' => 'Gestión de listas de precio', 'orden' => 1],
            ['modulo_id' => 3, 'nombre' => 'Precio Productos', 'codigo' => 'precio-productos', 'descripcion' => 'Precios por producto', 'orden' => 2],
            ['modulo_id' => 3, 'nombre' => 'Descuentos', 'codigo' => 'descuentos', 'descripcion' => 'Gestión de descuentos', 'orden' => 3],

            // Inventario (modulo_id = 4)
            ['modulo_id' => 4, 'nombre' => 'Stock', 'codigo' => 'stock', 'descripcion' => 'Control de inventario', 'orden' => 1],
            ['modulo_id' => 4, 'nombre' => 'Lotes', 'codigo' => 'lotes', 'descripcion' => 'Control de lotes', 'orden' => 2],
            ['modulo_id' => 4, 'nombre' => 'Seriales', 'codigo' => 'seriales', 'descripcion' => 'Control de seriales', 'orden' => 3],
            ['modulo_id' => 4, 'nombre' => 'Kardex', 'codigo' => 'kardex', 'descripcion' => 'Movimientos de inventario', 'orden' => 4],
            ['modulo_id' => 4, 'nombre' => 'Reconteos', 'codigo' => 'reconteos', 'descripcion' => 'Reconteos de inventario', 'orden' => 5],
            ['modulo_id' => 4, 'nombre' => 'Mermas', 'codigo' => 'mermas', 'descripcion' => 'Gestión de mermas', 'orden' => 6],
            ['modulo_id' => 4, 'nombre' => 'Traslados', 'codigo' => 'traslados', 'descripcion' => 'Traslados entre sucursales', 'orden' => 7],

            // Compras (modulo_id = 5)
            ['modulo_id' => 5, 'nombre' => 'Compras', 'codigo' => 'compras', 'descripcion' => 'Gestión de compras', 'orden' => 1],
            ['modulo_id' => 5, 'nombre' => 'Órdenes de Compra', 'codigo' => 'ordenes-de-compra', 'descripcion' => 'Órdenes de compra', 'orden' => 2],

            // Ventas (modulo_id = 6)
            ['modulo_id' => 6, 'nombre' => 'Ventas', 'codigo' => 'ventas', 'descripcion' => 'Gestión de ventas', 'orden' => 1],
            ['modulo_id' => 6, 'nombre' => 'Ventas de Campo', 'codigo' => 'ventas-de-campo', 'descripcion' => 'Ventas de campo', 'orden' => 2],
            ['modulo_id' => 6, 'nombre' => 'Cotizaciones', 'codigo' => 'cotizaciones', 'descripcion' => 'Gestión de cotizaciones', 'orden' => 3],

            // Cuentas (modulo_id = 7)
            ['modulo_id' => 7, 'nombre' => 'Cuentas por Cobrar', 'codigo' => 'cuentas-cobrar', 'descripcion' => 'Cuentas por cobrar', 'orden' => 1],
            ['modulo_id' => 7, 'nombre' => 'Cuentas por Pagar', 'codigo' => 'cuentas-pagar', 'descripcion' => 'Cuentas por pagar', 'orden' => 2],

            // Cartera (modulo_id = 8)
            ['modulo_id' => 8, 'nombre' => 'Cartera', 'codigo' => 'cartera', 'descripcion' => 'Gestión de cartera', 'orden' => 1],

            // Tesorería (modulo_id = 9)
            ['modulo_id' => 9, 'nombre' => 'Cuentas Bancarias', 'codigo' => 'cuentas-bancarias', 'descripcion' => 'Cuentas bancarias', 'orden' => 1],
            ['modulo_id' => 9, 'nombre' => 'Egresos', 'codigo' => 'egresos', 'descripcion' => 'Gestión de egresos', 'orden' => 2],
            ['modulo_id' => 9, 'nombre' => 'Recaudos', 'codigo' => 'recaudos', 'descripcion' => 'Gestión de recaudos', 'orden' => 3],
            ['modulo_id' => 9, 'nombre' => 'Conciliación', 'codigo' => 'conciliacion', 'descripcion' => 'Conciliación bancaria', 'orden' => 4],

            // Contabilidad (modulo_id = 10)
            ['modulo_id' => 10, 'nombre' => 'Cierre Contable', 'codigo' => 'cierre-contable', 'descripcion' => 'Cierre contable mensual', 'orden' => 1],
            ['modulo_id' => 10, 'nombre' => 'Estado de Cuenta', 'codigo' => 'estado-de-cuenta', 'descripcion' => 'Estado de cuenta general', 'orden' => 2],
            ['modulo_id' => 10, 'nombre' => 'Reporte IVA', 'codigo' => 'reporte-iva', 'descripcion' => 'Reporte de IVA', 'orden' => 3],
            ['modulo_id' => 10, 'nombre' => 'Gastos', 'codigo' => 'gastos', 'descripcion' => 'Gestión de gastos', 'orden' => 4],

            // Recursos Humanos (modulo_id = 11)
            ['modulo_id' => 11, 'nombre' => 'Empleados', 'codigo' => 'empleados', 'descripcion' => 'Gestión de empleados', 'orden' => 1],
            ['modulo_id' => 11, 'nombre' => 'Períodos', 'codigo' => 'periodos', 'descripcion' => 'Períodos de nómina', 'orden' => 2],
            ['modulo_id' => 11, 'nombre' => 'Liquidación Nómina', 'codigo' => 'liquidacion-nomina', 'descripcion' => 'Liquidación de nómina', 'orden' => 3],
            ['modulo_id' => 11, 'nombre' => 'Config. Nómina', 'codigo' => 'config-nomina', 'descripcion' => 'Configuración de nómina', 'orden' => 4],
            ['modulo_id' => 11, 'nombre' => 'Comisiones', 'codigo' => 'comisiones', 'descripcion' => 'Configuración de comisiones', 'orden' => 5],
            ['modulo_id' => 11, 'nombre' => 'Liquidar Comisiones', 'codigo' => 'liquidar-comisiones', 'descripcion' => 'Liquidación de comisiones', 'orden' => 6],

            // Reportes (modulo_id = 12)
            ['modulo_id' => 12, 'nombre' => 'Ventas', 'codigo' => 'ventas', 'descripcion' => 'Reportes de ventas', 'orden' => 1],
            ['modulo_id' => 12, 'nombre' => 'Inventario', 'codigo' => 'inventario', 'descripcion' => 'Reportes de inventario', 'orden' => 2],

            // Configuración (modulo_id = 13)
            ['modulo_id' => 13, 'nombre' => 'Clientes y Proveedores', 'codigo' => 'clientes-y-proveedores', 'descripcion' => 'Gestión de terceros', 'orden' => 1],
            ['modulo_id' => 13, 'nombre' => 'Cajas', 'codigo' => 'cajas', 'descripcion' => 'Gestión de cajas', 'orden' => 2],
            ['modulo_id' => 13, 'nombre' => 'Turnos', 'codigo' => 'turnos', 'descripcion' => 'Gestión de turnos', 'orden' => 3],
            ['modulo_id' => 13, 'nombre' => 'Sucursales', 'codigo' => 'sucursales', 'descripcion' => 'Gestión de sucursales', 'orden' => 4],
            ['modulo_id' => 13, 'nombre' => 'Usuarios', 'codigo' => 'usuarios', 'descripcion' => 'Gestión de usuarios', 'orden' => 5],
            ['modulo_id' => 13, 'nombre' => 'Módulos', 'codigo' => 'modulos', 'descripcion' => 'Gestión de módulos', 'orden' => 6],
        ];

        DB::table('submodulos')->insert($submodulos);
    }

    public function down(): void
    {
        Schema::dropIfExists('permiso_empresa');
        DB::table('submodulos')->delete();
        Schema::dropIfExists('submodulos');
    }
};
