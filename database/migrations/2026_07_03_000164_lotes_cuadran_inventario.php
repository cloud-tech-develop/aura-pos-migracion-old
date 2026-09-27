<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V164 — lotes que cuadran con el inventario y compra que los crea.
 *
 * Traducción de la migración Flyway V164__lotes_cuadran_inventario.sql
 * (aura-back-old). Cada sentencia en su propio DB::statement. Idempotente.
 *
 * Agrega empresa, fabricación, línea de compra y fecha al lote; índice FEFO y
 * código único por producto y sucursal (si no hay duplicados); la tabla
 * compra_detalle_lote; el registro lote_ajuste; y el cuadre inicial una sola
 * vez: el stock de productos con lotes que no está en ningún lote pasa a
 * "SIN-LOTE" (marca V164_cuadre_sin_lote en migracion_datos_aplicada).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE lote ADD COLUMN IF NOT EXISTS empresa_id        INTEGER
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE lote ADD COLUMN IF NOT EXISTS fecha_fabricacion DATE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE lote ADD COLUMN IF NOT EXISTS compra_detalle_id BIGINT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE lote ADD COLUMN IF NOT EXISTS created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE lote l
   SET empresa_id = s.empresa_id
  FROM sucursal s
 WHERE s.id = l.sucursal_id
   AND l.empresa_id IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_lote_fefo
    ON lote (producto_id, sucursal_id, fecha_vencimiento)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM lote
         GROUP BY producto_id, sucursal_id, UPPER(TRIM(codigo_lote))
        HAVING COUNT(*) > 1
    ) THEN
        RAISE NOTICE 'V164: hay lotes con código repetido por producto y sucursal; no se crea uq_lote_codigo';
    ELSE
        CREATE UNIQUE INDEX IF NOT EXISTS uq_lote_codigo
            ON lote (producto_id, sucursal_id, UPPER(TRIM(codigo_lote)));
    END IF;
END $$
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS compra_detalle_lote (
    id                 BIGSERIAL     PRIMARY KEY,
    compra_detalle_id  BIGINT        NOT NULL,
    lote_id            BIGINT        NOT NULL,
    cantidad_base      NUMERIC(18,6) NOT NULL,
    created_at         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_compra_detalle_lote_detalle FOREIGN KEY (compra_detalle_id) REFERENCES compra_detalle(id),
    CONSTRAINT fk_compra_detalle_lote_lote    FOREIGN KEY (lote_id)           REFERENCES lote(id)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_compra_detalle_lote_detalle ON compra_detalle_lote (compra_detalle_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_compra_detalle_lote_lote    ON compra_detalle_lote (lote_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS lote_ajuste (
    id              BIGSERIAL    PRIMARY KEY,
    lote_id         BIGINT       NOT NULL,
    empresa_id      INTEGER      NOT NULL,
    usuario_id      BIGINT,
    campo           VARCHAR(40)  NOT NULL,
    valor_anterior  VARCHAR(100),
    valor_nuevo     VARCHAR(100),
    motivo          VARCHAR(300) NOT NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_lote_ajuste_lote FOREIGN KEY (lote_id) REFERENCES lote(id)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_lote_ajuste_lote ON lote_ajuste (lote_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS migracion_datos_aplicada (
    clave        VARCHAR(80) PRIMARY KEY,
    aplicada_en  TIMESTAMP   NOT NULL DEFAULT now(),
    detalle      TEXT
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
DO $$
DECLARE
    v_creados     INTEGER := 0;
    v_ajustados   INTEGER := 0;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM migracion_datos_aplicada WHERE clave = 'V164_cuadre_sin_lote') THEN

        WITH diferencia AS (
            SELECT p.id AS producto_id, i.sucursal_id, p.empresa_id, p.costo,
                   i.stock_actual - COALESCE((SELECT SUM(l.stock_actual) FROM lote l
                                               WHERE l.producto_id = p.id
                                                 AND l.sucursal_id = i.sucursal_id
                                                 AND COALESCE(l.activo, true)), 0) AS falta
              FROM producto p
              JOIN inventario i ON i.producto_id = p.id
             WHERE p.maneja_lotes = true
               AND p.deleted_at IS NULL
        ),
        ajustados AS (
            UPDATE lote l
               SET stock_actual = l.stock_actual + d.falta,
                   activo = true
              FROM diferencia d
             WHERE d.falta > 0
               AND l.producto_id = d.producto_id
               AND l.sucursal_id = d.sucursal_id
               AND l.codigo_lote = 'SIN-LOTE'
            RETURNING l.id
        )
        SELECT COUNT(*) INTO v_ajustados FROM ajustados;

        INSERT INTO lote (producto_id, sucursal_id, empresa_id, codigo_lote, fecha_vencimiento,
                          stock_actual, costo_unitario, activo, created_at)
        SELECT p.id, i.sucursal_id, p.empresa_id, 'SIN-LOTE', NULL,
               i.stock_actual - COALESCE((SELECT SUM(l.stock_actual) FROM lote l
                                           WHERE l.producto_id = p.id
                                             AND l.sucursal_id = i.sucursal_id
                                             AND COALESCE(l.activo, true)), 0),
               COALESCE(p.costo, 0), true, CURRENT_TIMESTAMP
          FROM producto p
          JOIN inventario i ON i.producto_id = p.id
         WHERE p.maneja_lotes = true
           AND p.deleted_at IS NULL
           AND NOT EXISTS (SELECT 1 FROM lote l
                            WHERE l.producto_id = p.id
                              AND l.sucursal_id = i.sucursal_id
                              AND l.codigo_lote = 'SIN-LOTE')
           AND i.stock_actual - COALESCE((SELECT SUM(l.stock_actual) FROM lote l
                                           WHERE l.producto_id = p.id
                                             AND l.sucursal_id = i.sucursal_id
                                             AND COALESCE(l.activo, true)), 0) > 0;

        GET DIAGNOSTICS v_creados = ROW_COUNT;

        INSERT INTO migracion_datos_aplicada (clave, detalle)
        VALUES ('V164_cuadre_sin_lote',
                v_creados || ' lotes SIN-LOTE creados, ' || v_ajustados || ' completados');
    END IF;
END $$
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS lote_ajuste');
        DB::statement('DROP TABLE IF EXISTS compra_detalle_lote');
        DB::statement('DROP INDEX IF EXISTS uq_lote_codigo');
        DB::statement('DROP INDEX IF EXISTS idx_lote_fefo');
    }
};
