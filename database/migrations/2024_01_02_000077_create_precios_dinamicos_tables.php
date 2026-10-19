<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V14 — Precios dinámicos por cliente: precio especial, descuento por cliente y
 * precio por volumen.
 *
 * Traducción de la migración Flyway V14__precios_dinamicos.sql (aura-back-old),
 * que no tenía espejo. Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS precio_cliente (
    id BIGSERIAL PRIMARY KEY,
    empresa_id INT NOT NULL REFERENCES empresa(id),
    tercero_id INT NOT NULL REFERENCES tercero(id),
    producto_presentacion_id INT NOT NULL REFERENCES producto_presentacion(id),
    precio_especial DECIMAL(19,2) NOT NULL,
    fecha_inicio TIMESTAMP DEFAULT NOW(),
    fecha_fin TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    observaciones VARCHAR(500),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS descuento_cliente (
    id BIGSERIAL PRIMARY KEY,
    empresa_id INT NOT NULL REFERENCES empresa(id),
    tercero_id INT NOT NULL REFERENCES tercero(id),
    categoria_id INT REFERENCES categoria(id),
    porcentaje_descuento DECIMAL(5,2) NOT NULL,
    tipo_descuento VARCHAR(20) DEFAULT 'porcentaje',
    fecha_inicio TIMESTAMP DEFAULT NOW(),
    fecha_fin TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    observaciones VARCHAR(500),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS precio_volumen (
    id BIGSERIAL PRIMARY KEY,
    empresa_id INT NOT NULL REFERENCES empresa(id),
    producto_presentacion_id INT NOT NULL REFERENCES producto_presentacion(id),
    cantidad_minima INT,
    cantidad_maxima INT,
    precio_unitario DECIMAL(19,2) NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    observaciones VARCHAR(500),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP
)
MIG_SQL);

        foreach ([
            'CREATE INDEX IF NOT EXISTS idx_precio_cliente_empresa ON precio_cliente(empresa_id)',
            'CREATE INDEX IF NOT EXISTS idx_precio_cliente_tercero ON precio_cliente(tercero_id)',
            'CREATE INDEX IF NOT EXISTS idx_precio_cliente_producto ON precio_cliente(producto_presentacion_id)',
            'CREATE INDEX IF NOT EXISTS idx_precio_cliente_activo ON precio_cliente(activo) WHERE activo = TRUE',
            'CREATE INDEX IF NOT EXISTS idx_precio_cliente_vigencia ON precio_cliente(fecha_inicio, fecha_fin) WHERE activo = TRUE',
            'CREATE INDEX IF NOT EXISTS idx_descuento_cliente_empresa ON descuento_cliente(empresa_id)',
            'CREATE INDEX IF NOT EXISTS idx_descuento_cliente_tercero ON descuento_cliente(tercero_id)',
            'CREATE INDEX IF NOT EXISTS idx_descuento_cliente_categoria ON descuento_cliente(categoria_id)',
            'CREATE INDEX IF NOT EXISTS idx_descuento_cliente_activo ON descuento_cliente(activo) WHERE activo = TRUE',
            'CREATE INDEX IF NOT EXISTS idx_descuento_cliente_vigencia ON descuento_cliente(fecha_inicio, fecha_fin) WHERE activo = TRUE',
            'CREATE INDEX IF NOT EXISTS idx_precio_volumen_empresa ON precio_volumen(empresa_id)',
            'CREATE INDEX IF NOT EXISTS idx_precio_volumen_producto ON precio_volumen(producto_presentacion_id)',
            'CREATE INDEX IF NOT EXISTS idx_precio_volumen_activo ON precio_volumen(activo) WHERE activo = TRUE',
            'CREATE INDEX IF NOT EXISTS idx_precio_volumen_rango ON precio_volumen(cantidad_minima, cantidad_maxima)',
        ] as $sql) {
            DB::statement($sql);
        }
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS precio_volumen');
        DB::statement('DROP TABLE IF EXISTS descuento_cliente');
        DB::statement('DROP TABLE IF EXISTS precio_cliente');
    }
};
