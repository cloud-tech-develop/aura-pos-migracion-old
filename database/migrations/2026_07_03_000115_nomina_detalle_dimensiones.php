<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V115 — nomina detalle dimensiones.
 *
 * Traducción fiel de la migración Flyway V115__nomina_detalle_dimensiones.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V115: Fase 4.a — dimensionar la nómina por proyecto/frente ──────────────
--
-- ╔═══════════════════════════════════════════════════════════════════════════╗
-- ║ CAPA OPCIONAL. La nómina funciona completa SIN esta fase.                 ║
-- ║                                                                           ║
-- ║ Muchas empresas no manejan proyectos ni frentes: tienen empleados que son ║
-- ║ trabajadores normales y punto. Para ellas estas columnas quedan NULL, la  ║
-- ║ cascada cae al último paso, y el resultado es IDÉNTICO a no tenerlas.     ║
-- ╚═══════════════════════════════════════════════════════════════════════════╝
--
-- QUÉ ARREGLA: la V92 dice "habilita rentabilidad por obra" y dimensionó
-- `compra` y `gasto` con proyecto_id/frente_id — PERO NO `nomina`. La mano de
-- obra, normalmente el costo mayor, no llega al proyecto. La rentabilidad por
-- obra sale incompleta y siempre optimista.
--
-- Seguro sin tocar código: agrega columnas nullable con DEFAULT.

ALTER TABLE nomina_detalle
    ADD COLUMN IF NOT EXISTS proyecto_id        BIGINT REFERENCES proyecto(id),
    ADD COLUMN IF NOT EXISTS frente_id          BIGINT REFERENCES proyecto_frente(id),
    ADD COLUMN IF NOT EXISTS centro_costo_id    BIGINT,
    -- 100 = sin distribución. Una empresa sin proyectos genera una fila por
    -- concepto con las tres dimensiones nulas y este campo en 100 — que es
    -- exactamente lo que la Fase 3.b ya producía.
    ADD COLUMN IF NOT EXISTS porcentaje_distrib NUMERIC(5,2) NOT NULL DEFAULT 100
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_nomina_detalle_proyecto
    ON nomina_detalle(proyecto_id) WHERE proyecto_id IS NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN nomina_detalle.porcentaje_distrib IS
    'Porcentaje del concepto imputado a esta dimensión. Deja auditable el '
    'reparto. 100 = sin distribución (caso mayoritario).'
MIG_SQL);
    }

    public function down(): void
    {
        // Migración de datos/columnas (ver up). Reversa no automática.
    }
};
