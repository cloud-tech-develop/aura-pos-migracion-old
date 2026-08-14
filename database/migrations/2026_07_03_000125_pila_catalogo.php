<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V125 — pila catalogo.
 *
 * Traducción fiel de la migración Flyway V125__pila_catalogo.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V125: PILA P2 — catálogo genérico de códigos simples ─────────────────────
--
-- Catálogo GLOBAL clave-valor para las listas cortas de códigos oficiales:
-- tipo de planilla, tipo de aportante, clase/naturaleza de aportante, etc.
-- (Anexo Técnico 2, Res. 2388/2016). Evita una tabla por cada lista pequeña.
--
-- Habilita en el validador:
--   · PILA-PLA-002: tipo de planilla inválido/inactivo.
--   · PILA-APO-002: tipo de aportante inválido/inactivo.
--
-- REGLA DEL VALIDADOR (no inventar): subconjunto inicial con los códigos más
-- comunes y bien establecidos. Un código presente que NO esté en el catálogo se
-- reporta NO_EVALUABLE (no ERROR). Completar con el catálogo oficial del período.

CREATE TABLE IF NOT EXISTS pila_catalogo (
    id       BIGSERIAL    PRIMARY KEY,
    dominio  VARCHAR(30)  NOT NULL,   -- TIPO_PLANILLA | TIPO_APORTANTE | ...
    codigo   VARCHAR(10)  NOT NULL,
    nombre   VARCHAR(200) NOT NULL,
    activo   BOOLEAN      NOT NULL DEFAULT TRUE,

    CONSTRAINT uq_pila_catalogo UNIQUE (dominio, codigo)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_pila_catalogo_dominio ON pila_catalogo(dominio)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Subconjunto inicial (los que produce el generador para un empleador estándar):
INSERT INTO pila_catalogo (dominio, codigo, nombre) VALUES
    ('TIPO_PLANILLA',  'E', 'Empleados'),
    ('TIPO_APORTANTE', '1', 'Empleador')
ON CONFLICT (dominio, codigo) DO NOTHING
MIG_SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('pila_catalogo');
    }
};
