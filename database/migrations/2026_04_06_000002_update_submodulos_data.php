<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET session_replication_role = replica');
        DB::table('submodulos')->truncate();

        DB::table('submodulos')->insert([
            // Principal (modulo_id = 1)
            ['id' => 1, 'modulo_id' => 1, 'nombre' => 'Dashboard', 'codigo' => 'dashboard', 'descripcion' => 'Panel de control', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'modulo_id' => 1, 'nombre' => 'Punto de Venta', 'codigo' => 'punto-de-venta', 'descripcion' => 'Punto de venta', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Catálogo (modulo_id = 2)
            ['id' => 3, 'modulo_id' => 2, 'nombre' => 'Productos', 'codigo' => 'productos', 'descripcion' => 'Gestión de productos', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'modulo_id' => 2, 'nombre' => 'Categorías', 'codigo' => 'categorias', 'descripcion' => 'Categorías de productos', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'modulo_id' => 2, 'nombre' => 'Marcas', 'codigo' => 'marcas', 'descripcion' => 'Marcas de productos', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'modulo_id' => 2, 'nombre' => 'Unidades', 'codigo' => 'unidades', 'descripcion' => 'Unidades de medida', 'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'modulo_id' => 2, 'nombre' => 'Presentaciones', 'codigo' => 'presentaciones', 'descripcion' => 'Presentaciones de productos', 'orden' => 5, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'modulo_id' => 2, 'nombre' => 'Composiciones', 'codigo' => 'composiciones', 'descripcion' => 'Composiciones de productos', 'orden' => 6, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'modulo_id' => 2, 'nombre' => 'Etiquetas', 'codigo' => 'etiquetas', 'descripcion' => 'Etiquetas de productos', 'orden' => 7, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Precios (modulo_id = 3)
            ['id' => 10, 'modulo_id' => 3, 'nombre' => 'Listas de Precio', 'codigo' => 'listas-de-precio', 'descripcion' => 'Gestión de listas de precio', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'modulo_id' => 3, 'nombre' => 'Precio Productos', 'codigo' => 'precio-productos', 'descripcion' => 'Precios por producto', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'modulo_id' => 3, 'nombre' => 'Descuentos', 'codigo' => 'descuentos', 'descripcion' => 'Gestión de descuentos', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Inventario (modulo_id = 4)
            ['id' => 13, 'modulo_id' => 4, 'nombre' => 'Stock', 'codigo' => 'stock', 'descripcion' => 'Control de inventario', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 14, 'modulo_id' => 4, 'nombre' => 'Lotes', 'codigo' => 'lotes', 'descripcion' => 'Control de lotes', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 15, 'modulo_id' => 4, 'nombre' => 'Seriales', 'codigo' => 'seriales', 'descripcion' => 'Control de seriales', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 16, 'modulo_id' => 4, 'nombre' => 'Kardex', 'codigo' => 'kardex', 'descripcion' => 'Movimientos de inventario', 'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 17, 'modulo_id' => 4, 'nombre' => 'Reconteos', 'codigo' => 'reconteos', 'descripcion' => 'Reconteos de inventario', 'orden' => 5, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 18, 'modulo_id' => 4, 'nombre' => 'Mermas', 'codigo' => 'mermas', 'descripcion' => 'Gestión de mermas', 'orden' => 6, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 19, 'modulo_id' => 4, 'nombre' => 'Traslados', 'codigo' => 'traslados', 'descripcion' => 'Traslados entre sucursales', 'orden' => 7, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Compras (modulo_id = 5)
            ['id' => 20, 'modulo_id' => 5, 'nombre' => 'Compras', 'codigo' => 'compras', 'descripcion' => 'Gestión de compras', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 21, 'modulo_id' => 5, 'nombre' => 'Órdenes de Compra', 'codigo' => 'ordenes-de-compra', 'descripcion' => 'Órdenes de compra', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Ventas (modulo_id = 6)
            ['id' => 22, 'modulo_id' => 6, 'nombre' => 'Ventas', 'codigo' => 'ventas', 'descripcion' => 'Gestión de ventas', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 23, 'modulo_id' => 6, 'nombre' => 'Ventas de Campo', 'codigo' => 'ventas-de-campo', 'descripcion' => 'Ventas de campo', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 24, 'modulo_id' => 6, 'nombre' => 'Cotizaciones', 'codigo' => 'cotizaciones', 'descripcion' => 'Gestión de cotizaciones', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 53, 'modulo_id' => 6, 'nombre' => 'Devoluciones', 'codigo' => 'devoluciones', 'descripcion' => 'Devoluciones', 'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Cuentas (modulo_id = 7)
            ['id' => 25, 'modulo_id' => 7, 'nombre' => 'Cuentas por Cobrar', 'codigo' => 'cuentas-por-cobrar', 'descripcion' => 'Cuentas por cobrar', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 26, 'modulo_id' => 7, 'nombre' => 'Cuentas por Pagar', 'codigo' => 'cuentas-por-pagar', 'descripcion' => 'Cuentas por pagar', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Cartera (modulo_id = 8)
            ['id' => 27, 'modulo_id' => 8, 'nombre' => 'Cartera', 'codigo' => 'cartera', 'descripcion' => 'Gestión de cartera', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Tesorería (modulo_id = 9)
            ['id' => 28, 'modulo_id' => 9, 'nombre' => 'Cuentas Bancarias', 'codigo' => 'cuentas-bancarias', 'descripcion' => 'Cuentas bancarias', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 29, 'modulo_id' => 9, 'nombre' => 'Egresos', 'codigo' => 'egresos', 'descripcion' => 'Gestión de egresos', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 30, 'modulo_id' => 9, 'nombre' => 'Recaudos', 'codigo' => 'recaudos', 'descripcion' => 'Gestión de recaudos', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 31, 'modulo_id' => 9, 'nombre' => 'Conciliación', 'codigo' => 'conciliacion', 'descripcion' => 'Conciliación bancaria', 'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Contabilidad (modulo_id = 10)
            ['id' => 32, 'modulo_id' => 10, 'nombre' => 'Cierre Contable', 'codigo' => 'cierre-contable', 'descripcion' => 'Cierre contable mensual', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 33, 'modulo_id' => 10, 'nombre' => 'Estado de Cuenta', 'codigo' => 'estado-de-cuenta', 'descripcion' => 'Estado de cuenta general', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 34, 'modulo_id' => 10, 'nombre' => 'Reporte IVA', 'codigo' => 'reporte-iva', 'descripcion' => 'Reporte de IVA', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 35, 'modulo_id' => 10, 'nombre' => 'Gastos', 'codigo' => 'gastos', 'descripcion' => 'Gestión de gastos', 'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 51, 'modulo_id' => 10, 'nombre' => 'Plan de Cuentas', 'codigo' => 'plan-de-cuentas', 'descripcion' => 'Plan de Cuentas', 'orden' => 5, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 52, 'modulo_id' => 10, 'nombre' => 'Asientos Contables', 'codigo' => 'asientos-contables', 'descripcion' => 'Asientos Contables', 'orden' => 6, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Recursos Humanos (modulo_id = 11)
            ['id' => 36, 'modulo_id' => 11, 'nombre' => 'Empleados', 'codigo' => 'empleados', 'descripcion' => 'Gestión de empleados', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 37, 'modulo_id' => 11, 'nombre' => 'Períodos', 'codigo' => 'periodos', 'descripcion' => 'Períodos de nómina', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 38, 'modulo_id' => 11, 'nombre' => 'Liquidación Nómina', 'codigo' => 'liquidacion-nomina', 'descripcion' => 'Liquidación de nómina', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 39, 'modulo_id' => 11, 'nombre' => 'Config. Nómina', 'codigo' => 'config-nomina', 'descripcion' => 'Configuración de nómina', 'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 40, 'modulo_id' => 11, 'nombre' => 'Comisiones', 'codigo' => 'comisiones', 'descripcion' => 'Configuración de comisiones', 'orden' => 5, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 41, 'modulo_id' => 11, 'nombre' => 'Liquidar Comisiones', 'codigo' => 'liquidar-comisiones', 'descripcion' => 'Liquidación de comisiones', 'orden' => 6, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Reportes (modulo_id = 12)
            ['id' => 42, 'modulo_id' => 12, 'nombre' => 'Ventas', 'codigo' => 'ventas', 'descripcion' => 'Reportes de ventas', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 43, 'modulo_id' => 12, 'nombre' => 'Inventario', 'codigo' => 'inventario', 'descripcion' => 'Reportes de inventario', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 50, 'modulo_id' => 12, 'nombre' => 'Reportes Avanzados', 'codigo' => 'reportes-avanzados', 'descripcion' => 'Reportes Avanzados', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Terceros y Sucursales (modulo_id = 13)
            ['id' => 44, 'modulo_id' => 13, 'nombre' => 'Clientes y Proveedores', 'codigo' => 'clientes-y-proveedores', 'descripcion' => 'Gestión de terceros', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 47, 'modulo_id' => 13, 'nombre' => 'Sucursales', 'codigo' => 'sucursales', 'descripcion' => 'Gestión de sucursales', 'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 49, 'modulo_id' => 13, 'nombre' => 'Módulos', 'codigo' => 'modulos', 'descripcion' => 'Gestión de módulos', 'orden' => 6, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Vendedores (modulo_id = 14)
            ['id' => 54, 'modulo_id' => 14, 'nombre' => 'Vendedores', 'codigo' => 'vendedores', 'descripcion' => '', 'orden' => 0, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 55, 'modulo_id' => 14, 'nombre' => 'Locales', 'codigo' => 'locales', 'descripcion' => '', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 56, 'modulo_id' => 14, 'nombre' => 'Rutas', 'codigo' => 'rutas', 'descripcion' => '', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 57, 'modulo_id' => 14, 'nombre' => 'Visitas', 'codigo' => 'visitas', 'descripcion' => '', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 58, 'modulo_id' => 14, 'nombre' => 'Mi Perfil', 'codigo' => 'mi-perfil', 'descripcion' => '', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 59, 'modulo_id' => 14, 'nombre' => 'Escanear QR', 'codigo' => 'escanear-qr', 'descripcion' => '', 'orden' => 5, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::statement('SELECT setval(\'submodulos_id_seq\', (SELECT MAX(id) FROM submodulos))');
        DB::statement('SET session_replication_role = DEFAULT');
    }

    public function down(): void
    {
        DB::statement('SET session_replication_role = replica');
        DB::table('submodulos')->truncate();

        DB::table('submodulos')->insert([
            // Principal (modulo_id = 1)
            ['id' => 1, 'modulo_id' => 1, 'nombre' => 'Dashboard', 'codigo' => 'dashboard', 'descripcion' => 'Panel de control', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'modulo_id' => 1, 'nombre' => 'Punto de Venta', 'codigo' => 'pos', 'descripcion' => 'Punto de venta', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Catálogo (modulo_id = 2)
            ['id' => 3, 'modulo_id' => 2, 'nombre' => 'Productos', 'codigo' => 'productos', 'descripcion' => 'Gestión de productos', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'modulo_id' => 2, 'nombre' => 'Categorías', 'codigo' => 'categorias', 'descripcion' => 'Categorías de productos', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'modulo_id' => 2, 'nombre' => 'Marcas', 'codigo' => 'marcas', 'descripcion' => 'Marcas de productos', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'modulo_id' => 2, 'nombre' => 'Unidades', 'codigo' => 'unidades', 'descripcion' => 'Unidades de medida', 'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'modulo_id' => 2, 'nombre' => 'Presentaciones', 'codigo' => 'presentaciones', 'descripcion' => 'Presentaciones de productos', 'orden' => 5, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'modulo_id' => 2, 'nombre' => 'Composiciones', 'codigo' => 'composiciones', 'descripcion' => 'Composiciones de productos', 'orden' => 6, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'modulo_id' => 2, 'nombre' => 'Etiquetas', 'codigo' => 'etiquetas', 'descripcion' => 'Etiquetas de productos', 'orden' => 7, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Precios (modulo_id = 3)
            ['id' => 10, 'modulo_id' => 3, 'nombre' => 'Listas de Precio', 'codigo' => 'listas-de-precio', 'descripcion' => 'Gestión de listas de precio', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'modulo_id' => 3, 'nombre' => 'Precio Productos', 'codigo' => 'precio-productos', 'descripcion' => 'Precios por producto', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'modulo_id' => 3, 'nombre' => 'Descuentos', 'codigo' => 'descuentos', 'descripcion' => 'Gestión de descuentos', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Inventario (modulo_id = 4)
            ['id' => 13, 'modulo_id' => 4, 'nombre' => 'Stock', 'codigo' => 'stock', 'descripcion' => 'Control de inventario', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 14, 'modulo_id' => 4, 'nombre' => 'Lotes', 'codigo' => 'lotes', 'descripcion' => 'Control de lotes', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 15, 'modulo_id' => 4, 'nombre' => 'Seriales', 'codigo' => 'seriales', 'descripcion' => 'Control de seriales', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 16, 'modulo_id' => 4, 'nombre' => 'Kardex', 'codigo' => 'kardex', 'descripcion' => 'Movimientos de inventario', 'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 17, 'modulo_id' => 4, 'nombre' => 'Reconteos', 'codigo' => 'reconteos', 'descripcion' => 'Reconteos de inventario', 'orden' => 5, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 18, 'modulo_id' => 4, 'nombre' => 'Mermas', 'codigo' => 'mermas', 'descripcion' => 'Gestión de mermas', 'orden' => 6, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 19, 'modulo_id' => 4, 'nombre' => 'Traslados', 'codigo' => 'traslados', 'descripcion' => 'Traslados entre sucursales', 'orden' => 7, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Compras (modulo_id = 5)
            ['id' => 20, 'modulo_id' => 5, 'nombre' => 'Compras', 'codigo' => 'compras', 'descripcion' => 'Gestión de compras', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 21, 'modulo_id' => 5, 'nombre' => 'Órdenes de Compra', 'codigo' => 'ordenes-de-compra', 'descripcion' => 'Órdenes de compra', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Ventas (modulo_id = 6)
            ['id' => 22, 'modulo_id' => 6, 'nombre' => 'Ventas', 'codigo' => 'ventas', 'descripcion' => 'Gestión de ventas', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 23, 'modulo_id' => 6, 'nombre' => 'Ventas de Campo', 'codigo' => 'ventas-de-campo', 'descripcion' => 'Ventas de campo', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 24, 'modulo_id' => 6, 'nombre' => 'Cotizaciones', 'codigo' => 'cotizaciones', 'descripcion' => 'Gestión de cotizaciones', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Cuentas (modulo_id = 7)
            ['id' => 25, 'modulo_id' => 7, 'nombre' => 'Cuentas por Cobrar', 'codigo' => 'cuentas-cobrar', 'descripcion' => 'Cuentas por cobrar', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 26, 'modulo_id' => 7, 'nombre' => 'Cuentas por Pagar', 'codigo' => 'cuentas-pagar', 'descripcion' => 'Cuentas por pagar', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Cartera (modulo_id = 8)
            ['id' => 27, 'modulo_id' => 8, 'nombre' => 'Cartera', 'codigo' => 'cartera', 'descripcion' => 'Gestión de cartera', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Tesorería (modulo_id = 9)
            ['id' => 28, 'modulo_id' => 9, 'nombre' => 'Cuentas Bancarias', 'codigo' => 'cuentas-bancarias', 'descripcion' => 'Cuentas bancarias', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 29, 'modulo_id' => 9, 'nombre' => 'Egresos', 'codigo' => 'egresos', 'descripcion' => 'Gestión de egresos', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 30, 'modulo_id' => 9, 'nombre' => 'Recaudos', 'codigo' => 'recaudos', 'descripcion' => 'Gestión de recaudos', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 31, 'modulo_id' => 9, 'nombre' => 'Conciliación', 'codigo' => 'conciliacion', 'descripcion' => 'Conciliación bancaria', 'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Contabilidad (modulo_id = 10)
            ['id' => 32, 'modulo_id' => 10, 'nombre' => 'Cierre Contable', 'codigo' => 'cierre-contable', 'descripcion' => 'Cierre contable mensual', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 33, 'modulo_id' => 10, 'nombre' => 'Estado de Cuenta', 'codigo' => 'estado-de-cuenta', 'descripcion' => 'Estado de cuenta general', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 34, 'modulo_id' => 10, 'nombre' => 'Reporte IVA', 'codigo' => 'reporte-iva', 'descripcion' => 'Reporte de IVA', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 35, 'modulo_id' => 10, 'nombre' => 'Gastos', 'codigo' => 'gastos', 'descripcion' => 'Gestión de gastos', 'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Recursos Humanos (modulo_id = 11)
            ['id' => 36, 'modulo_id' => 11, 'nombre' => 'Empleados', 'codigo' => 'empleados', 'descripcion' => 'Gestión de empleados', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 37, 'modulo_id' => 11, 'nombre' => 'Períodos', 'codigo' => 'periodos', 'descripcion' => 'Períodos de nómina', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 38, 'modulo_id' => 11, 'nombre' => 'Liquidación Nómina', 'codigo' => 'liquidacion-nomina', 'descripcion' => 'Liquidación de nómina', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 39, 'modulo_id' => 11, 'nombre' => 'Config. Nómina', 'codigo' => 'config-nomina', 'descripcion' => 'Configuración de nómina', 'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 40, 'modulo_id' => 11, 'nombre' => 'Comisiones', 'codigo' => 'comisiones', 'descripcion' => 'Configuración de comisiones', 'orden' => 5, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 41, 'modulo_id' => 11, 'nombre' => 'Liquidar Comisiones', 'codigo' => 'liquidar-comisiones', 'descripcion' => 'Liquidación de comisiones', 'orden' => 6, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Reportes (modulo_id = 12)
            ['id' => 42, 'modulo_id' => 12, 'nombre' => 'Ventas', 'codigo' => 'ventas', 'descripcion' => 'Reportes de ventas', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 43, 'modulo_id' => 12, 'nombre' => 'Inventario', 'codigo' => 'inventario', 'descripcion' => 'Reportes de inventario', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],

            // Configuración (modulo_id = 13)
            ['id' => 44, 'modulo_id' => 13, 'nombre' => 'Clientes y Proveedores', 'codigo' => 'clientes-y-proveedores', 'descripcion' => 'Gestión de terceros', 'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 45, 'modulo_id' => 13, 'nombre' => 'Cajas', 'codigo' => 'cajas', 'descripcion' => 'Gestión de cajas', 'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 46, 'modulo_id' => 13, 'nombre' => 'Turnos', 'codigo' => 'turnos', 'descripcion' => 'Gestión de turnos', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 47, 'modulo_id' => 13, 'nombre' => 'Sucursales', 'codigo' => 'sucursales', 'descripcion' => 'Gestión de sucursales', 'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 48, 'modulo_id' => 13, 'nombre' => 'Usuarios', 'codigo' => 'usuarios', 'descripcion' => 'Gestión de usuarios', 'orden' => 5, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 49, 'modulo_id' => 13, 'nombre' => 'Módulos', 'codigo' => 'modulos', 'descripcion' => 'Gestión de módulos', 'orden' => 6, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::statement('SELECT setval(\'submodulos_id_seq\', (SELECT MAX(id) FROM submodulos))');
        DB::statement('SET session_replication_role = DEFAULT');
    }
};
