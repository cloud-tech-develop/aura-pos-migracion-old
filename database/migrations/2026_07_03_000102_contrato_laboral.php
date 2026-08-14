<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V102 — contrato laboral.
 *
 * Traducción fiel de la migración Flyway V102__contrato_laboral.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V102: Fase 2 — el contrato laboral como entidad ─────────────────────────
--
-- Hoy `empleados` embebe el contrato: fecha_ingreso, fecha_retiro, salario_base,
-- tipo_contrato. Eso impide:
--   · Que una persona tenga dos vínculos simultáneos.
--   · Historial de contratos y renovaciones.
--   · Historial salarial — HOY UN AUMENTO SOBRESCRIBE salario_base Y EL DATO
--     ANTERIOR SE PIERDE. Sin él no hay retroactivos, no hay auditoría, y PILA
--     no puede calcular la bandera `vsp` (variación permanente de salario).
--
-- Ventana: nómina no la usa nadie todavía. Este cambio nunca va a ser más
-- barato que ahora.
--
-- Seguro sin tocar código: solo crea tablas nuevas.

CREATE TABLE IF NOT EXISTS contrato_laboral (
    id                  BIGSERIAL     PRIMARY KEY,
    empresa_id          INT           NOT NULL REFERENCES empresa(id),
    empleado_id         BIGINT        NOT NULL REFERENCES empleados(id),

    tipo_contrato       VARCHAR(30)   NOT NULL,
    cargo               VARCHAR(100),
    fecha_inicio        DATE          NOT NULL,
    fecha_fin           DATE,                    -- NULL = indefinido

    salario_base        NUMERIC(15,2) NOT NULL,
    es_salario_integral BOOLEAN       NOT NULL DEFAULT FALSE,
    periodicidad        VARCHAR(20),             -- NULL = hereda de nomina_config

    -- Multi-vínculo: una persona puede tener varios contratos activos.
    -- `es_principal` marca cuál manda para reportes de un solo vínculo.
    es_principal        BOOLEAN       NOT NULL DEFAULT TRUE,

    -- Retefuente (Fase 4.5). Se deja aquí para no volver a alterar la tabla.
    procedimiento_retefuente VARCHAR(3) NOT NULL DEFAULT '1',
    porcentaje_fijo_retencion NUMERIC(5,2),

    estado              VARCHAR(20)   NOT NULL DEFAULT 'ACTIVO',
    causa_retiro        VARCHAR(40),
    observacion         VARCHAR(500),

    created_at          TIMESTAMP     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP     NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMP,

    CONSTRAINT chk_contrato_tipo CHECK (tipo_contrato IN
        ('INDEFINIDO', 'FIJO', 'OBRA_LABOR', 'PRESTACION_SERVICIOS', 'APRENDIZAJE')),
    CONSTRAINT chk_contrato_estado CHECK (estado IN
        ('ACTIVO', 'SUSPENDIDO', 'TERMINADO')),
    CONSTRAINT chk_contrato_proc_rf CHECK (procedimiento_retefuente IN ('1', '2')),
    CONSTRAINT chk_contrato_fechas CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_contrato_empresa  ON contrato_laboral(empresa_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_contrato_empleado ON contrato_laboral(empleado_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_contrato_activo
    ON contrato_laboral(empleado_id, estado) WHERE deleted_at IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Historial salarial ──────────────────────────────────────────────────────
-- OBLIGATORIO aunque se descarte el multi-vínculo. Sin esto no hay retroactivos,
-- no hay auditoría, y PILA no calcula `vsp`.
CREATE TABLE IF NOT EXISTS contrato_salario_historial (
    id          BIGSERIAL     PRIMARY KEY,
    contrato_id BIGINT        NOT NULL REFERENCES contrato_laboral(id) ON DELETE CASCADE,
    salario     NUMERIC(15,2) NOT NULL,
    fecha_desde DATE          NOT NULL,
    fecha_hasta DATE,                            -- NULL = vigente
    motivo      VARCHAR(200),
    created_at  TIMESTAMP     NOT NULL DEFAULT NOW(),
    created_by  BIGINT,
    CONSTRAINT chk_csh_fechas CHECK (fecha_hasta IS NULL OR fecha_hasta >= fecha_desde)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_csh_contrato ON contrato_salario_historial(contrato_id, fecha_desde)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Solo un salario vigente por contrato
CREATE UNIQUE INDEX IF NOT EXISTS ux_csh_vigente
    ON contrato_salario_historial(contrato_id) WHERE fecha_hasta IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Renovaciones / prórrogas ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS contrato_renovacion (
    id            BIGSERIAL   PRIMARY KEY,
    contrato_id   BIGINT      NOT NULL REFERENCES contrato_laboral(id) ON DELETE CASCADE,
    fecha_inicial DATE        NOT NULL,
    fecha_final   DATE        NOT NULL,
    descripcion   VARCHAR(300),
    created_at    TIMESTAMP   NOT NULL DEFAULT NOW(),
    created_by    BIGINT,
    CONSTRAINT chk_cr_fechas CHECK (fecha_final >= fecha_inicial)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_cr_contrato ON contrato_renovacion(contrato_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Distribución del costo laboral por centro de costo ──────────────────────
-- Paso 2 de la cascada de distribución (Fase 4.a): se usa cuando el empleado
-- NO tiene asistencia por frente. Nullable en la práctica: quien no lo usa,
-- no crea filas.
CREATE TABLE IF NOT EXISTS contrato_centro_costo (
    id              BIGSERIAL    PRIMARY KEY,
    contrato_id     BIGINT       NOT NULL REFERENCES contrato_laboral(id) ON DELETE CASCADE,
    centro_costo_id BIGINT       NOT NULL,
    porcentaje      NUMERIC(5,2) NOT NULL,
    CONSTRAINT chk_ccc_porcentaje CHECK (porcentaje > 0 AND porcentaje <= 100)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_ccc_contrato ON contrato_centro_costo(contrato_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Que los porcentajes sumen 100 por contrato se valida en aplicación:
-- un CHECK no puede mirar otras filas.

-- ── Backfill desde `empleados` ──────────────────────────────────────────────
-- Un contrato por empleado existente, con sus datos actuales.
-- Nómina está vacía, así que esto es prácticamente no-op — pero se deja por
-- si algún ambiente tiene empleados de prueba.
INSERT INTO contrato_laboral (
    empresa_id, empleado_id, tipo_contrato, cargo,
    fecha_inicio, fecha_fin, salario_base, es_principal, estado
)
SELECT
    e.empresa_id,
    e.id,
    COALESCE(e.tipo_contrato, 'INDEFINIDO'),
    e.cargo,
    e.fecha_ingreso,
    e.fecha_retiro,
    e.salario_base,
    TRUE,
    CASE WHEN e.fecha_retiro IS NOT NULL THEN 'TERMINADO'
         WHEN e.activo = FALSE           THEN 'TERMINADO'
         ELSE 'ACTIVO' END
  FROM empleados e
 WHERE NOT EXISTS (SELECT 1 FROM contrato_laboral c WHERE c.empleado_id = e.id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- Primera fila del historial salarial: el salario actual desde el ingreso.
INSERT INTO contrato_salario_historial (contrato_id, salario, fecha_desde, motivo)
SELECT c.id, c.salario_base, c.fecha_inicio, 'Salario inicial (backfill V102)'
  FROM contrato_laboral c
 WHERE NOT EXISTS (SELECT 1 FROM contrato_salario_historial h WHERE h.contrato_id = c.id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Columnas duplicadas en `empleados` ──────────────────────────────────────
--
-- ⚠️ NINGUNA se puede borrar sin migrar antes el código que las lee.
--    Ver el bloque de abajo: `cargo` en particular ES CARGA ESTRUCTURAL.

COMMENT ON COLUMN empleados.tipo_contrato IS
    'DUPLICADO con contrato_laboral.tipo_contrato. Preferir el del contrato. No borrar aún.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN empleados.salario_base IS
    'DUPLICADO con contrato_laboral.salario_base. La verdad histórica está en contrato_salario_historial. No borrar aún.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN empleados.fecha_ingreso IS
    'DUPLICADO con contrato_laboral.fecha_inicio. No borrar aún.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN empleados.fecha_retiro IS
    'DUPLICADO con contrato_laboral.fecha_fin. No borrar aún.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ═══════════════════════════════════════════════════════════════════════════
-- ⛔ `empleados.cargo` — NO DEPRECAR. LO USA EL RASTREO DE VENDEDORES.
-- ═══════════════════════════════════════════════════════════════════════════
--
-- `contrato_laboral.cargo` NO lo reemplaza. Hoy `empleados.cargo` sostiene
-- tres cosas que se romperían al borrarlo:
--
--  1. LISTADO DE VENDEDORES
--       EmpleadoJPARepository.findByEmpresaIdAndActivoTrueAndCargoIgnoreCase(
--           empresaId, "VENDEDOR")
--       → EmpleadoServiceImpl.listarVendedores()
--     Sin esto, el módulo de ventas se queda sin vendedores.
--
--  2. RESOLUCIÓN DEL TIPO DE EMPLEADO
--       EmpleadoServiceImpl:~348 y UsuarioServiceImpl:~207 comparan
--       `empleado.getCargo()` contra `TipoEmpleadoEntity.getNombre()`
--       (por nombre, IgnoreCase — no por FK).
--     Sin esto, el empleado no resuelve su tipo y se rompe el alta de usuario.
--
--  3. COMISIONES
--       comision_venta.vendedor_id      → empleados(id)   [V60]
--       comision_liquidacion.vendedor_id → empleados(id)  [V60]
--     Estas van por FK a `empleados`, no por cargo, así que sobreviven — pero
--     dependen de que listarVendedores() siga devolviendo a la gente correcta.
--
-- QUÉ HACER SI ALGÚN DÍA SE UNIFICA:
--   · `cargo` es del CONTRATO conceptualmente (una persona puede cambiar de
--     cargo al renovar), pero el código lo lee del empleado.
--   · Migrar exige: cambiar las 3 rutas de arriba a leer del contrato activo,
--     decidir qué pasa con multi-vínculo (¿vendedor en un contrato y bodeguero
--     en otro?), y recién ahí borrar.
--   · Mientras tanto: `contrato_laboral.cargo` se llena al crear el contrato,
--     pero `empleados.cargo` SIGUE SIENDO LA FUENTE para vendedores y tipos.
--
-- NO son iguales: `empleados.cargo` es texto libre comparado contra
-- `tipo_empleado.nombre`; `contrato_laboral.cargo` es descriptivo.
-- ═══════════════════════════════════════════════════════════════════════════
COMMENT ON COLUMN empleados.cargo IS
    'NO DEPRECAR. Fuente del listado de vendedores (findBy...CargoIgnoreCase) y '
    'de la resolución de tipo_empleado (comparación por nombre). '
    'contrato_laboral.cargo NO lo reemplaza — ver el bloque de notas en V102.'
MIG_SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_centro_costo');
        Schema::dropIfExists('contrato_renovacion');
        Schema::dropIfExists('contrato_salario_historial');
        Schema::dropIfExists('contrato_laboral');
    }
};
