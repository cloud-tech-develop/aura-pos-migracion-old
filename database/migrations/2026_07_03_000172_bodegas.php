<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V172 — bodegas dentro de la sucursal.
 *
 * Hasta aquí el saldo vivía por sucursal: `inventario (sucursal_id, producto_id)`.
 * Eso alcanza para una tienda, no para un negocio con bodega principal, bodega de
 * averías, nevera o vitrina dentro del mismo local. Y no había a quién
 * responsabilizar de un faltante.
 *
 * Desde aquí **el saldo vive en la bodega**. La sucursal sigue existiendo (es la
 * que factura y tiene caja) y su stock es la suma de sus bodegas.
 *
 * Para no romper nada:
 *   1. Cada sucursal recibe una "Bodega Principal".
 *   2. Todo lo existente (inventario, kardex, lotes, seriales y cada documento)
 *      se rellena con la bodega principal de su sucursal.
 *   3. `sucursal_id` NO se elimina de ninguna tabla: queda como columna derivada
 *      para que todo reporte que agrupa por sucursal siga dando lo mismo.
 *   4. Si un documento no dice bodega, el backend resuelve la principal
 *      (BodegaService.resolver). Operar sin bodegas funciona igual que antes.
 *
 * Traducción de la migración Flyway V172__bodegas.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    /** Tablas que llevan stock o son documentos de inventario. */
    private const TABLAS = [
        'inventario', 'movimiento_inventario', 'lote', 'serial_producto',
        'reconteos', 'compra', 'venta', 'merma', 'obsequio',
        'consumo_interno', 'devolucion', 'orden_compra',
    ];

    public function up(): void
    {
        // ── 1. La bodega ──────────────────────────────────────────────────
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS bodega (
    id                     BIGSERIAL    PRIMARY KEY,
    empresa_id             INTEGER      NOT NULL,
    sucursal_id            INTEGER      NOT NULL,
    codigo                 VARCHAR(20),
    nombre                 VARCHAR(80)  NOT NULL,
    responsable_usuario_id INTEGER,
    es_principal           BOOLEAN      NOT NULL DEFAULT FALSE,
    permite_venta          BOOLEAN      NOT NULL DEFAULT TRUE,
    ubicacion              VARCHAR(120),
    observacion            VARCHAR(300),
    activa                 BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at             TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP,

    CONSTRAINT fk_bodega_empresa     FOREIGN KEY (empresa_id)             REFERENCES empresa(id),
    CONSTRAINT fk_bodega_sucursal    FOREIGN KEY (sucursal_id)            REFERENCES sucursal(id),
    CONSTRAINT fk_bodega_responsable FOREIGN KEY (responsable_usuario_id) REFERENCES usuario(id)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_bodega_nombre ON bodega (sucursal_id, LOWER(nombre))
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_bodega_codigo
    ON bodega (empresa_id, UPPER(codigo)) WHERE codigo IS NOT NULL
MIG_SQL);

        // Una sola principal por sucursal: es la que resuelve el backend.
        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_bodega_principal ON bodega (sucursal_id) WHERE es_principal
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_bodega_empresa ON bodega (empresa_id, activa)
MIG_SQL);

        // ── 2. Una bodega principal por sucursal existente ────────────────
        DB::statement(<<<'MIG_SQL'
INSERT INTO bodega (empresa_id, sucursal_id, codigo, nombre, es_principal, permite_venta, activa)
SELECT s.empresa_id, s.id, 'BOD-' || s.id, 'Bodega Principal', TRUE, TRUE, TRUE
  FROM sucursal s
 WHERE NOT EXISTS (SELECT 1 FROM bodega b WHERE b.sucursal_id = s.id)
MIG_SQL);

        // ── 3. bodega_id en todo lo que mueve stock ───────────────────────
        // Ojo: ADD COLUMN IF NOT EXISTS no detecta un tipo distinto. Si alguna
        // ya existiera con otro tipo, ddl-auto=validate tumba el arranque.
        foreach (self::TABLAS as $t) {
            DB::statement("ALTER TABLE {$t} ADD COLUMN IF NOT EXISTS bodega_id BIGINT");
        }

        // El traslado ahora es entre bodegas (pueden ser de la misma sucursal).
        DB::statement('ALTER TABLE traslado ADD COLUMN IF NOT EXISTS bodega_origen_id BIGINT');
        DB::statement('ALTER TABLE traslado ADD COLUMN IF NOT EXISTS bodega_destino_id BIGINT');

        // Sin esto, anular un traslado entre dos bodegas de la MISMA sucursal
        // no sabría a cuál devolver el serial.
        DB::statement('ALTER TABLE documento_serial ADD COLUMN IF NOT EXISTS bodega_anterior_id BIGINT');

        // ── 4. Backfill: todo lo viejo es de la bodega principal ──────────
        foreach (self::TABLAS as $t) {
            DB::statement(<<<SQL
UPDATE {$t} d SET bodega_id = b.id
  FROM bodega b
 WHERE b.sucursal_id = d.sucursal_id
   AND b.es_principal
   AND d.bodega_id IS NULL
SQL);
        }

        DB::statement(<<<'MIG_SQL'
UPDATE traslado t SET bodega_origen_id = b.id
  FROM bodega b
 WHERE b.sucursal_id = t.sucursal_origen_id AND b.es_principal AND t.bodega_origen_id IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE traslado t SET bodega_destino_id = b.id
  FROM bodega b
 WHERE b.sucursal_id = t.sucursal_destino_id AND b.es_principal AND t.bodega_destino_id IS NULL
MIG_SQL);

        // ── 5. Llaves foráneas y NOT NULL donde el dato siempre existe ────
        foreach (self::TABLAS as $t) {
            DB::statement("ALTER TABLE {$t} DROP CONSTRAINT IF EXISTS fk_{$t}_bodega");
            DB::statement(
                "ALTER TABLE {$t} ADD CONSTRAINT fk_{$t}_bodega
                 FOREIGN KEY (bodega_id) REFERENCES bodega(id)"
            );

            // NOT NULL solo si el backfill no dejó huérfanas: una fila con una
            // sucursal borrada haría fallar la migración entera.
            // El driver pgsql devuelve el booleano como bool o como 't'/'f'
            // según la versión, por eso se normaliza en vez de confiar en él.
            $fila = DB::selectOne("SELECT COUNT(*) AS sin_bodega FROM {$t} WHERE bodega_id IS NULL");
            if ($fila && (int) $fila->sin_bodega === 0) {
                DB::statement("ALTER TABLE {$t} ALTER COLUMN bodega_id SET NOT NULL");
            }
        }

        DB::statement('ALTER TABLE traslado DROP CONSTRAINT IF EXISTS fk_traslado_bodega_origen');
        DB::statement(<<<'MIG_SQL'
ALTER TABLE traslado ADD CONSTRAINT fk_traslado_bodega_origen
    FOREIGN KEY (bodega_origen_id) REFERENCES bodega(id)
MIG_SQL);

        DB::statement('ALTER TABLE traslado DROP CONSTRAINT IF EXISTS fk_traslado_bodega_destino');
        DB::statement(<<<'MIG_SQL'
ALTER TABLE traslado ADD CONSTRAINT fk_traslado_bodega_destino
    FOREIGN KEY (bodega_destino_id) REFERENCES bodega(id)
MIG_SQL);

        // ── 6. El saldo pasa a ser por bodega ─────────────────────────────
        // La unicidad vieja (sucursal_id, producto_id) impediría justo lo que
        // viene a hacer esta migración: el mismo producto en dos bodegas del
        // mismo local. Se busca por estructura porque el nombre del índice
        // viene del esquema original y no es igual en toda instalación.
        DB::statement(<<<'MIG_SQL'
DO $$
DECLARE r RECORD;
BEGIN
    FOR r IN
        SELECT c.conname
          FROM pg_constraint c
          JOIN pg_class t ON t.oid = c.conrelid
         WHERE t.relname = 'inventario'
           AND c.contype = 'u'
           AND (SELECT array_agg(a.attname::TEXT ORDER BY a.attname::TEXT)
                  FROM unnest(c.conkey) k
                  JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = k)
               = ARRAY['producto_id', 'sucursal_id']
    LOOP
        EXECUTE format('ALTER TABLE inventario DROP CONSTRAINT %I', r.conname);
    END LOOP;

    FOR r IN
        SELECT i.indexrelid::regclass AS idxname
          FROM pg_index i
          JOIN pg_class t ON t.oid = i.indrelid
         WHERE t.relname = 'inventario'
           AND i.indisunique
           AND NOT i.indisprimary
           AND (SELECT array_agg(a.attname::TEXT ORDER BY a.attname::TEXT)
                  FROM unnest(i.indkey) k
                  JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = k)
               = ARRAY['producto_id', 'sucursal_id']
    LOOP
        EXECUTE format('DROP INDEX %s', r.idxname);
    END LOOP;
END $$
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_inventario_bodega_producto
    ON inventario (bodega_id, producto_id)
MIG_SQL);

        // ── 7. Índices de consulta ────────────────────────────────────────
        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_movimiento_inventario_bodega
    ON movimiento_inventario (bodega_id, producto_id, id)
MIG_SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_lote_bodega ON lote (bodega_id, producto_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_serial_bodega ON serial_producto (bodega_id, producto_id)');

        // El submódulo del menú va aparte: docs/sql/menu_submodulo_bodegas.sql
        // (el sidebar filtra por LABEL normalizado contra un Set global).
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_serial_bodega');
        DB::statement('DROP INDEX IF EXISTS idx_lote_bodega');
        DB::statement('DROP INDEX IF EXISTS idx_movimiento_inventario_bodega');
        DB::statement('DROP INDEX IF EXISTS uq_inventario_bodega_producto');

        // Se devuelve la unicidad vieja: sin ella el saldo por sucursal queda
        // sin proteger al volver atrás.
        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_inventario_sucursal_producto
    ON inventario (sucursal_id, producto_id)
MIG_SQL);

        DB::statement('ALTER TABLE traslado DROP CONSTRAINT IF EXISTS fk_traslado_bodega_destino');
        DB::statement('ALTER TABLE traslado DROP CONSTRAINT IF EXISTS fk_traslado_bodega_origen');
        DB::statement('ALTER TABLE traslado DROP COLUMN IF EXISTS bodega_destino_id');
        DB::statement('ALTER TABLE traslado DROP COLUMN IF EXISTS bodega_origen_id');
        DB::statement('ALTER TABLE documento_serial DROP COLUMN IF EXISTS bodega_anterior_id');

        foreach (self::TABLAS as $t) {
            DB::statement("ALTER TABLE {$t} DROP CONSTRAINT IF EXISTS fk_{$t}_bodega");
            DB::statement("ALTER TABLE {$t} DROP COLUMN IF EXISTS bodega_id");
        }

        DB::statement('DROP TABLE IF EXISTS bodega');
    }
};
