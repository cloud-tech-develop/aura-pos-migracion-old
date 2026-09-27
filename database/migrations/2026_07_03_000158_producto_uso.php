<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * V158 — uso del producto: VENTA / INSUMO / AMBOS.
 *
 * Traducción de la migración Flyway V158__producto_uso.sql (aura-back-old).
 *
 * "Insumo" se simulaba apagando visible_en_pos. Va en columna aparte de
 * tipo_producto porque son ejes distintos: la harina es PESABLE e INSUMO.
 *
 * Idempotente. El backfill corre solo cuando la columna se crea, para no pisar
 * lo que el usuario ya haya clasificado:
 *   oculto del POS                         → INSUMO
 *   visible y componente de alguna receta  → AMBOS
 *   el resto                               → VENTA
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('producto', 'uso_producto')) {
            DB::statement(<<<'MIG_SQL'
ALTER TABLE producto
    ADD COLUMN uso_producto VARCHAR(10) NOT NULL DEFAULT 'VENTA'
MIG_SQL);

            DB::statement(<<<'MIG_SQL'
UPDATE producto
   SET uso_producto = 'INSUMO'
 WHERE visible_en_pos = false
MIG_SQL);

            DB::statement(<<<'MIG_SQL'
UPDATE producto p
   SET uso_producto = 'AMBOS'
 WHERE p.uso_producto = 'VENTA'
   AND EXISTS (SELECT 1 FROM producto_composicion pc
                WHERE pc.producto_hijo_id = p.id)
MIG_SQL);
        }

        $check = DB::selectOne("SELECT 1 AS existe FROM pg_constraint WHERE conname = 'chk_producto_uso'");
        if ($check === null) {
            DB::statement(<<<'MIG_SQL'
ALTER TABLE producto
    ADD CONSTRAINT chk_producto_uso CHECK (uso_producto IN ('VENTA', 'INSUMO', 'AMBOS'))
MIG_SQL);
        }

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN producto.uso_producto IS
    'VENTA | INSUMO | AMBOS. Un INSUMO no se muestra en el POS y es el que ofrece el selector de componentes de receta.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_producto_empresa_uso ON producto (empresa_id, uso_producto)
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_producto_empresa_uso');
        DB::statement('ALTER TABLE producto DROP CONSTRAINT IF EXISTS chk_producto_uso');
        DB::statement('ALTER TABLE producto DROP COLUMN IF EXISTS uso_producto');
    }
};
