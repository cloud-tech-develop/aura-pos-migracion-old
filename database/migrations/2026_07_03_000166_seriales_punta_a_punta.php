<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V166 — seriales de punta a punta.
 *
 * Traducción de la migración Flyway V166__seriales_punta_a_punta.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 *
 * Agrega empresa, costo, ingreso, compra de origen, documento de salida y
 * garantía al serial; cambia el UNIQUE(serial) global por uno por producto;
 * agrega producto.meses_garantia y la tabla documento_serial.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE serial_producto ADD COLUMN IF NOT EXISTS empresa_id             INTEGER
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE serial_producto ADD COLUMN IF NOT EXISTS costo                  NUMERIC(15,2)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE serial_producto ADD COLUMN IF NOT EXISTS fecha_ingreso          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE serial_producto ADD COLUMN IF NOT EXISTS compra_detalle_id      BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE serial_producto ADD COLUMN IF NOT EXISTS documento_salida_tipo  VARCHAR(30)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE serial_producto ADD COLUMN IF NOT EXISTS documento_salida_id    BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE serial_producto ADD COLUMN IF NOT EXISTS garantia_cliente_hasta DATE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE serial_producto sp
   SET empresa_id = s.empresa_id
  FROM sucursal s
 WHERE s.id = sp.sucursal_id
   AND sp.empresa_id IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE serial_producto DROP CONSTRAINT IF EXISTS serial_producto_serial_key
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM serial_producto
         GROUP BY producto_id, UPPER(TRIM(serial))
        HAVING COUNT(*) > 1
    ) THEN
        RAISE NOTICE 'V166: hay seriales repetidos dentro de un producto; no se crea uq_serial_producto';
    ELSE
        CREATE UNIQUE INDEX IF NOT EXISTS uq_serial_producto
            ON serial_producto (producto_id, UPPER(TRIM(serial)));
    END IF;
END $$
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_serial_producto_disponible
    ON serial_producto (producto_id, sucursal_id, estado)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE producto ADD COLUMN IF NOT EXISTS meses_garantia INTEGER
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS documento_serial (
    id               BIGSERIAL    PRIMARY KEY,
    origen           VARCHAR(30)  NOT NULL,
    detalle_id       BIGINT       NOT NULL,
    serial_id        BIGINT       NOT NULL,
    estado_anterior  VARCHAR(20),
    sucursal_anterior_id INTEGER,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_documento_serial_serial FOREIGN KEY (serial_id) REFERENCES serial_producto(id)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_documento_serial_detalle ON documento_serial (origen, detalle_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_documento_serial_serial  ON documento_serial (serial_id)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS documento_serial');
        DB::statement('DROP INDEX IF EXISTS idx_serial_producto_disponible');
        DB::statement('DROP INDEX IF EXISTS uq_serial_producto');
        DB::statement('ALTER TABLE producto DROP COLUMN IF EXISTS meses_garantia');
    }
};
