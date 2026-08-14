<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V123 — pila tipo cotizante.
 *
 * Traducción fiel de la migración Flyway V123__pila_tipo_cotizante.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V123: PILA P2 — catálogo de tipos de cotizante + subsistemas obligatorios ─
--
-- Catálogo GLOBAL (no por empresa): son códigos oficiales del Anexo Técnico 2 de
-- la Resolución 2388/2016. Habilita en el validador:
--   · PILA-COT-002: validez del tipo de cotizante (hoy NO_EVALUABLE).
--   · Paso 10 (entidades): exigir EPS/AFP/ARL/CCF SOLO cuando el tipo de
--     cotizante está obligado a ese subsistema.
--
-- IMPORTANTE (regla del validador: no inventar códigos): esto es un SUBCONJUNTO
-- INICIAL con los tipos más comunes y sus obligaciones bien establecidas en la
-- ley. Un código que NO esté aquí se reporta NO_EVALUABLE (no ERROR): el
-- validador no afirma que sea inválido, solo que no puede verificarlo. Completar
-- con el catálogo oficial vigente del período antes de dar por cerrada P2.

CREATE TABLE IF NOT EXISTS pila_tipo_cotizante (
    codigo          VARCHAR(5)   PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL,
    oblig_salud     BOOLEAN      NOT NULL DEFAULT TRUE,
    oblig_pension   BOOLEAN      NOT NULL DEFAULT TRUE,
    oblig_arl       BOOLEAN      NOT NULL DEFAULT TRUE,
    oblig_ccf       BOOLEAN      NOT NULL DEFAULT TRUE,
    activo          BOOLEAN      NOT NULL DEFAULT TRUE,
    vigencia_desde  DATE,
    vigencia_hasta  DATE
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Subconjunto inicial (obligaciones establecidas en la ley):
--   01 Dependiente          → salud, pensión, ARL, CCF
--   02 Servicio doméstico    → salud, pensión, ARL, CCF (Ley 1595/2012 → CCF)
--   51 Tiempo parcial (Dec. 2616/2013) → salud, pensión, ARL, CCF
INSERT INTO pila_tipo_cotizante (codigo, nombre, oblig_salud, oblig_pension, oblig_arl, oblig_ccf)
VALUES
    ('01', 'Dependiente',                     TRUE, TRUE, TRUE, TRUE),
    ('02', 'Servicio doméstico',              TRUE, TRUE, TRUE, TRUE),
    ('51', 'Trabajador de tiempo parcial',    TRUE, TRUE, TRUE, TRUE)
ON CONFLICT (codigo) DO NOTHING
MIG_SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('pila_tipo_cotizante');
    }
};
