<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V95 · E11 · Información exógena DIAN (C7): catálogo de formatos y conceptos,
 * mapeo cuenta/rango PUC → concepto por empresa, lotes versionados con sus
 * líneas (tercero × concepto × valor) y errores del validador previo. Incluye
 * seed del catálogo global y mapeos default para empresas existentes.
 * Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Catálogo global (no depende de la empresa) ────────────────────────
        if (!Schema::hasTable('exogena_formato')) {
            Schema::create('exogena_formato', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('codigo', 10)->unique();  // 1001, 1005, 1006, 1007, 1008, 1009, 2276
                $table->string('nombre', 200);
                $table->integer('version_dian')->default(1);
                $table->boolean('activo')->default(true);
            });
        }

        if (!Schema::hasTable('exogena_concepto')) {
            Schema::create('exogena_concepto', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('formato_id');
                $table->string('codigo', 10);            // 5001, 5002… (o el propio formato)
                $table->string('nombre', 255);

                $table->unique(['formato_id', 'codigo']);
                $table->foreign('formato_id')->references('id')->on('exogena_formato');
            });
        }

        // ── Parametrización por empresa: rango PUC → concepto + tipo de valor ─
        // cuenta_hasta NULL = match por prefijo de cuenta_desde; con valor =
        // rango lexicográfico. Al generar gana el mapeo con el prefijo más
        // específico.
        if (!Schema::hasTable('exogena_mapeo_cuenta')) {
            Schema::create('exogena_mapeo_cuenta', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('concepto_id');
                $table->string('cuenta_desde', 10);
                $table->string('cuenta_hasta', 10)->nullable();
                $table->string('tipo_valor', 20);        // MOVIMIENTO_DB | MOVIMIENTO_CR | SALDO_DB | SALDO_CR
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('concepto_id')->references('id')->on('exogena_concepto');
                $table->index('empresa_id', 'idx_exogena_mapeo_empresa');
            });
        }

        // ── Lotes versionados ─────────────────────────────────────────────────
        if (!Schema::hasTable('exogena_lote')) {
            Schema::create('exogena_lote', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedBigInteger('formato_id');
                $table->integer('anio');
                $table->integer('version')->default(1);
                $table->string('estado', 15)->default('BORRADOR'); // BORRADOR | APROBADO
                $table->decimal('cuantia_menor_umbral', 18, 2)->default(100000);
                $table->unsignedBigInteger('generado_por')->nullable();
                $table->timestamp('generado_en')->useCurrent();
                $table->unsignedBigInteger('aprobado_por')->nullable();
                $table->timestamp('aprobado_en')->nullable();

                $table->unique(['empresa_id', 'formato_id', 'anio', 'version']);
                $table->foreign('formato_id')->references('id')->on('exogena_formato');
            });
        }

        if (!Schema::hasTable('exogena_linea')) {
            Schema::create('exogena_linea', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('lote_id');
                $table->unsignedBigInteger('concepto_id');
                $table->unsignedBigInteger('tercero_id')->nullable(); // NULL = cuantías menores (222222222)
                $table->decimal('valor', 18, 2);
                $table->boolean('cuantia_menor')->default(false);

                $table->foreign('lote_id')->references('id')->on('exogena_lote');
                $table->foreign('concepto_id')->references('id')->on('exogena_concepto');
                $table->index('lote_id', 'idx_exogena_linea_lote');
            });
        }

        if (!Schema::hasTable('exogena_error')) {
            Schema::create('exogena_error', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('lote_id')->nullable();
                $table->unsignedInteger('empresa_id');
                $table->integer('anio');
                $table->string('tipo', 40);   // TERCERO_INCOMPLETO | SIN_MAPEO | COMPROBANTE_BORRADOR | PERIODO_ABIERTO | SIN_TERCERO
                $table->string('detalle', 300)->nullable();
                $table->unsignedBigInteger('tercero_id')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('lote_id')->references('id')->on('exogena_lote');
                $table->index('lote_id', 'idx_exogena_error_lote');
            });
        }

        // ── Seed del catálogo (idempotente) ───────────────────────────────────
        DB::statement(<<<'SQL'
            INSERT INTO exogena_formato (codigo, nombre, version_dian)
            SELECT v.codigo, v.nombre, v.version_dian
            FROM (VALUES ('1001', 'Pagos y abonos en cuenta y retenciones practicadas', 10),
                         ('1005', 'Impuesto sobre las ventas descontable',               7),
                         ('1006', 'Impuesto sobre las ventas generado',                  7),
                         ('1007', 'Ingresos recibidos',                                  9),
                         ('1008', 'Saldo de cuentas por cobrar al 31 de diciembre',      7),
                         ('1009', 'Saldo de cuentas por pagar al 31 de diciembre',       7),
                         ('2276', 'Ingresos y retenciones por rentas de trabajo',        4)
                 ) AS v(codigo, nombre, version_dian)
            WHERE NOT EXISTS (SELECT 1 FROM exogena_formato f WHERE f.codigo = v.codigo)
        SQL);

        DB::statement(<<<'SQL'
            INSERT INTO exogena_concepto (formato_id, codigo, nombre)
            SELECT f.id, v.concepto, v.nombre
            FROM (VALUES ('1001', '5001', 'Salarios y demás pagos laborales'),
                         ('1001', '5002', 'Honorarios'),
                         ('1001', '5003', 'Comisiones'),
                         ('1001', '5004', 'Servicios'),
                         ('1001', '5005', 'Arrendamientos'),
                         ('1001', '5006', 'Intereses y rendimientos financieros'),
                         ('1001', '5007', 'Compra de activos movibles'),
                         ('1001', '5008', 'Compra de activos fijos'),
                         ('1001', '5016', 'Los demás costos y deducciones'),
                         ('1005', '1005', 'IVA descontable'),
                         ('1006', '1006', 'IVA generado'),
                         ('1007', '4001', 'Ingresos brutos de actividades ordinarias'),
                         ('1007', '4002', 'Otros ingresos brutos'),
                         ('1008', '1315', 'Cuentas por cobrar a clientes y otros'),
                         ('1009', '2201', 'Saldo de pasivos con proveedores y otros'),
                         ('2276', '2276', 'Pagos por rentas de trabajo')
                 ) AS v(formato, concepto, nombre)
            JOIN exogena_formato f ON f.codigo = v.formato
            WHERE NOT EXISTS (SELECT 1 FROM exogena_concepto c
                              WHERE c.formato_id = f.id AND c.codigo = v.concepto)
        SQL);

        // ── Mapeos default para empresas EXISTENTES (prefijos del PUC seed) ───
        // cuenta_hasta NULL = prefijo. El más específico gana (5105 le gana a 51).
        DB::statement(<<<'SQL'
            INSERT INTO exogena_mapeo_cuenta (empresa_id, concepto_id, cuenta_desde, tipo_valor)
            SELECT e.id, c.id, v.desde, v.tipo_valor
            FROM (VALUES ('1001', '5001', '5105',   'MOVIMIENTO_DB'),
                         ('1001', '5006', '5305',   'MOVIMIENTO_DB'),
                         ('1001', '5007', '1435',   'MOVIMIENTO_DB'),
                         ('1001', '5008', '15',     'MOVIMIENTO_DB'),
                         ('1001', '5016', '51',     'MOVIMIENTO_DB'),
                         ('1001', '5016', '52',     'MOVIMIENTO_DB'),
                         ('1001', '5016', '53',     'MOVIMIENTO_DB'),
                         ('1005', '1005', '240802', 'MOVIMIENTO_DB'),
                         ('1006', '1006', '240801', 'MOVIMIENTO_CR'),
                         ('1007', '4001', '41',     'MOVIMIENTO_CR'),
                         ('1007', '4002', '42',     'MOVIMIENTO_CR'),
                         ('1008', '1315', '1305',   'SALDO_DB'),
                         ('1009', '2201', '2205',   'SALDO_CR'),
                         ('2276', '2276', '5105',   'MOVIMIENTO_DB')
                 ) AS v(formato, concepto, desde, tipo_valor)
            JOIN exogena_formato f  ON f.codigo = v.formato
            JOIN exogena_concepto c ON c.formato_id = f.id AND c.codigo = v.concepto
            CROSS JOIN empresa e
            WHERE NOT EXISTS (SELECT 1 FROM exogena_mapeo_cuenta m
                              WHERE m.empresa_id = e.id AND m.concepto_id = c.id
                                AND m.cuenta_desde = v.desde)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('exogena_error');
        Schema::dropIfExists('exogena_linea');
        Schema::dropIfExists('exogena_lote');
        Schema::dropIfExists('exogena_mapeo_cuenta');
        Schema::dropIfExists('exogena_concepto');
        Schema::dropIfExists('exogena_formato');
    }
};
