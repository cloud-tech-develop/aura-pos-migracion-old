<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V159 — presentaciones: el factor pasa a ser "unidades base que contiene".
 *
 * Traducción de la migración Flyway V159__presentacion_factor_contiene.sql (aura-back-old).
 *
 * El back dividía (factor = cuántas presentaciones caben en 1 base) y las
 * pantallas enseñaban lo contrario. Desde V159 cantidad base = cantidad × factor.
 * Para no mover inventario, las presentaciones existentes se voltean
 * (factor := 1/factor): vender 1 UNIDAD de una paca ×25 sigue descontando 0,04.
 *
 *   1. factor_conversion a NUMERIC(18,8) (en la base real era 38,2: 1/12 quedaría 0,08).
 *   2. Volteo una sola vez, marcado en migracion_datos_aplicada (compartida con
 *      Flyway). Respaldo en producto_presentacion_bak_v159.
 *   3. codigo_barras deja de ser único global; la unicidad por empresa la valida el servicio.
 *
 * ⚠ Desplegar junto con el back que multiplica.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'MIG_SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
         WHERE table_schema = current_schema()
           AND table_name   = 'producto_presentacion'
           AND column_name  = 'factor_conversion'
           AND (numeric_precision IS DISTINCT FROM 18 OR numeric_scale IS DISTINCT FROM 8)
    ) THEN
        ALTER TABLE producto_presentacion
            ALTER COLUMN factor_conversion TYPE NUMERIC(18,8);
    END IF;
END $$;
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS migracion_datos_aplicada (
    clave        VARCHAR(80) PRIMARY KEY,
    aplicada_en  TIMESTAMP   NOT NULL DEFAULT now(),
    detalle      TEXT
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON TABLE migracion_datos_aplicada IS
    'Migraciones de DATOS no repetibles (voltear, recalcular). La comparten Flyway y Laravel para que ninguna corra dos veces.'
MIG_SQL);

        DB::unprepared(<<<'MIG_SQL'
DO $$
DECLARE
    v_filas INTEGER;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM migracion_datos_aplicada WHERE clave = 'V159_factor_contiene') THEN
        CREATE TABLE IF NOT EXISTS producto_presentacion_bak_v159 AS
            SELECT id, producto_id, nombre, factor_conversion, now() AS respaldado_en
              FROM producto_presentacion;

        UPDATE producto_presentacion
           SET factor_conversion = ROUND(1.0 / factor_conversion, 8)
         WHERE factor_conversion > 0
           AND factor_conversion <> 1;

        GET DIAGNOSTICS v_filas = ROW_COUNT;

        INSERT INTO migracion_datos_aplicada (clave, detalle)
        VALUES ('V159_factor_contiene', v_filas || ' presentaciones volteadas (factor := 1/factor)');
    END IF;
END $$;
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN producto_presentacion.factor_conversion IS
    'Unidades base que CONTIENE la presentación (Caja x10 = 10). Cantidad base = cantidad x factor. Menor que 1 = presentación más pequeña que la base.'
MIG_SQL);

        DB::unprepared(<<<'MIG_SQL'
DO $$
DECLARE
    r RECORD;
BEGIN
    FOR r IN
        SELECT conname
          FROM pg_constraint
         WHERE conrelid = 'producto_presentacion'::regclass
           AND contype  = 'u'
           AND pg_get_constraintdef(oid) = 'UNIQUE (codigo_barras)'
    LOOP
        EXECUTE format('ALTER TABLE producto_presentacion DROP CONSTRAINT %I', r.conname);
    END LOOP;

    FOR r IN
        SELECT i.relname AS indice
          FROM pg_index x
          JOIN pg_class i ON i.oid = x.indexrelid
         WHERE x.indrelid = 'producto_presentacion'::regclass
           AND x.indisunique
           AND NOT x.indisprimary
           AND pg_get_indexdef(x.indexrelid) ~ '\(codigo_barras\)$'
    LOOP
        EXECUTE format('DROP INDEX %I', r.indice);
    END LOOP;
END $$;
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_producto_presentacion_codigo_barras
    ON producto_presentacion (codigo_barras)
MIG_SQL);
    }

    /**
     * No se revierte el volteo en automático: si ya se vendió con la regla nueva,
     * volver al factor anterior descuadra. Para deshacerlo, restaurar desde
     * producto_presentacion_bak_v159 a mano.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_producto_presentacion_codigo_barras');
    }
};
