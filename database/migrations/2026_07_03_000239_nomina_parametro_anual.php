<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V239 — Salario mínimo y auxilio de transporte por año (nomina_parametro_anual),
 * UVT 2026 corregida y defaults de nomina_config al valor vigente.
 *
 * Traducción de la migración Flyway V239__nomina_parametro_anual.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nomina_parametro_anual (
    agno                INT            PRIMARY KEY,
    smmlv               NUMERIC(15,2)  NOT NULL,
    auxilio_transporte  NUMERIC(15,2)  NOT NULL,
    norma               VARCHAR(200),
    created_at          TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO nomina_parametro_anual (agno, smmlv, auxilio_transporte, norma) VALUES
    (2023, 1160000.00, 140606.00, 'Decretos de salario mínimo y auxilio 2023'),
    (2024, 1300000.00, 162000.00, 'Decretos de salario mínimo y auxilio 2024'),
    (2025, 1423500.00, 200000.00, 'Decretos de salario mínimo y auxilio 2025'),
    (2026, 1750905.00, 249095.00, 'Decretos de salario mínimo y auxilio 2026')
ON CONFLICT (agno) DO NOTHING
MIG_SQL);

        DB::statement('INSERT INTO uvt_valor (agno, valor) VALUES (2025, 49799.00) ON CONFLICT (agno) DO NOTHING');

        DB::statement('UPDATE uvt_valor SET valor = 52374.00 WHERE agno = 2026 AND valor = 49799.00');

        DB::statement('ALTER TABLE nomina_config ALTER COLUMN smmlv SET DEFAULT 1750905');

        DB::statement('ALTER TABLE nomina_config ALTER COLUMN auxilio_transporte SET DEFAULT 249095');
    }

    public function down(): void
    {
        // Sin reversa: son valores de ley.
    }
};
