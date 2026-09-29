<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V87 · E2 · Piezas 4 y 5 de tesorería.
 * Compra con destino contable: el débito puede ir a gasto (5195 papelería),
 * activo (15xx) u otra cuenta en vez de inventario, con centro de costo
 * propagado a todas las líneas del asiento. Sobregiro bancario (enfoque A):
 * el banco puede quedar en negativo hasta el cupo. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE compra ADD COLUMN IF NOT EXISTS centro_costo_id BIGINT');
        DB::statement('ALTER TABLE compra ADD COLUMN IF NOT EXISTS cuenta_contable_id BIGINT');

        DB::statement('ALTER TABLE cuenta_bancaria ADD COLUMN IF NOT EXISTS permite_sobregiro BOOLEAN NOT NULL DEFAULT FALSE');
        DB::statement('ALTER TABLE cuenta_bancaria ADD COLUMN IF NOT EXISTS cupo_sobregiro NUMERIC(18,2)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE compra DROP COLUMN IF EXISTS centro_costo_id');
        DB::statement('ALTER TABLE compra DROP COLUMN IF EXISTS cuenta_contable_id');
        DB::statement('ALTER TABLE cuenta_bancaria DROP COLUMN IF EXISTS permite_sobregiro');
        DB::statement('ALTER TABLE cuenta_bancaria DROP COLUMN IF EXISTS cupo_sobregiro');
    }
};
