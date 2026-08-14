<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V126 — pila aportante config.
 *
 * Traducción fiel de la migración Flyway V126__pila_aportante_config.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V126: PILA P4b — configuración del aportante por empresa ─────────────────
--
-- Los datos del aportante que el operador exige en el encabezado PILA y que HOY
-- no existen en `empresa` (el generador los dejaba en TODO): clasificación del
-- aportante, actividad económica (CIIU), operador, forma de presentación y el
-- representante legal desagregado. Una fila por empresa.
--
-- Al poblarla, el generador la vuelca al encabezado y se resuelven las
-- advertencias de P4a (rep. legal / actividad económica) y el tipo de aportante
-- deja de salir NO_EVALUABLE.

CREATE TABLE IF NOT EXISTS pila_aportante_config (
    empresa_id                INT          PRIMARY KEY REFERENCES empresa(id),

    tipo_aportante            VARCHAR(5),
    clase_aportante           VARCHAR(5),
    naturaleza_aportante      VARCHAR(5),
    cod_actividad_economica   VARCHAR(10),
    cod_operador              VARCHAR(10),
    forma_presentacion        VARCHAR(5),

    -- Representante legal: obligatorio y desagregado.
    rep_legal_tipo_documento  VARCHAR(5),
    rep_legal_documento       VARCHAR(30),
    rep_legal_apellido1       VARCHAR(40),
    rep_legal_apellido2       VARCHAR(40),
    rep_legal_nombre1         VARCHAR(40),
    rep_legal_nombre2         VARCHAR(40),

    updated_at                TIMESTAMP    NOT NULL DEFAULT NOW()
)
MIG_SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('pila_aportante_config');
    }
};
