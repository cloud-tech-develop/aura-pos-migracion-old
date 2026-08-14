<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V124 — pila entidad.
 *
 * Traducción fiel de la migración Flyway V124__pila_entidad.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V124: PILA P2b — catálogo de entidades (EPS/AFP/ARL/CCF) con vigencia ─────
--
-- Catálogo GLOBAL de administradoras de seguridad social, con su código PILA
-- oficial y vigencia. Habilita en el validador:
--   · PILA-ENT-005: código de entidad inválido (no existe en el catálogo).
--   · vigencia de la entidad para el período.
--
-- REGLA DEL VALIDADOR (no inventar): esta tabla se crea VACÍA. Los códigos de
-- EPS/AFP/ARL/CCF son cientos y cambian por período; deben cargarse del catálogo
-- oficial del operador/UGPP. Mientras un tipo de entidad NO tenga filas cargadas,
-- el validador NO valida ese código (no afirma que sea inválido): solo verifica
-- la PRESENCIA (P2a). En cuanto se carga el catálogo de un tipo, se activa la
-- validación de existencia y vigencia para ese tipo, sin tocar código.

CREATE TABLE IF NOT EXISTS pila_entidad (
    id              BIGSERIAL    PRIMARY KEY,
    tipo            VARCHAR(5)   NOT NULL,   -- EPS | AFP | ARL | CCF
    codigo          VARCHAR(10)  NOT NULL,   -- código PILA oficial
    nombre          VARCHAR(200) NOT NULL,
    vigencia_desde  DATE,
    vigencia_hasta  DATE,
    activo          BOOLEAN      NOT NULL DEFAULT TRUE,

    CONSTRAINT chk_pila_entidad_tipo CHECK (tipo IN ('EPS', 'AFP', 'ARL', 'CCF')),
    CONSTRAINT uq_pila_entidad UNIQUE (tipo, codigo)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_pila_entidad_tipo ON pila_entidad(tipo)
MIG_SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('pila_entidad');
    }
};
