<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V101 — cuenta bancaria banco fk.
 *
 * Traducción fiel de la migración Flyway V101__cuenta_bancaria_banco_fk.sql (aura-back-old): cada
 * sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- ── V101: Fase 1.d — cablear el rol BANCO ───────────────────────────────────
--
-- El problema (D10): `es_banco` existe como rol y TerceroQueryRepository.listarBancos()
-- lo usa para el "selector de banco (nómina/tesorería)". Pero al persistir:
--
--     cuenta_bancaria.banco  VARCHAR(200)   ← texto libre
--     empleados.banco        VARCHAR(100)   ← texto libre
--
-- El selector muestra terceros; lo que se guarda es el NOMBRE como string.
-- El rol no apunta a nada.
--
-- Por qué importa más allá de lo cosmético: es exactamente el bug que rompería
-- las afiliaciones a EPS (Fase 5.5). "SURA", "Sura EPS" y "EPS SURA" son el
-- mismo NIT y tres strings distintos → el operador de PILA rechaza el archivo.
-- Arreglarlo aquí valida el patrón sobre un caso simple antes de replicarlo.
--
-- NO AFECTA CONTABILIDAD (verificado): el motor de asientos resuelve el lado
-- del banco con cuenta_bancaria.cuenta_contable_id → plan_cuenta, y la
-- contrapartida con tesoreria_movimiento.contrapartida_cuenta_id (ver V66).
-- `banco` solo se lee para display: CuentaPdfService, FacturaQueryRepository
-- y copias entre DTOs. Ningún lector decide un débito o un crédito.

-- ── cuenta_bancaria.tercero_id ya existe (V96) — solo falta la FK real ───────
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_cuenta_bancaria_tercero') THEN
        ALTER TABLE cuenta_bancaria
            ADD CONSTRAINT fk_cuenta_bancaria_tercero
            FOREIGN KEY (tercero_id) REFERENCES tercero(id);
    END IF;
END $$
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_cuenta_bancaria_tercero
    ON cuenta_bancaria(tercero_id) WHERE tercero_id IS NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── empleados.banco → tercero ───────────────────────────────────────────────
-- El banco donde se le consigna el sueldo. `tercero.banco` (V97) es el string
-- migrado; esta es la FK que lo reemplaza.
ALTER TABLE tercero
    ADD COLUMN IF NOT EXISTS banco_tercero_id BIGINT REFERENCES tercero(id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
COMMENT ON COLUMN tercero.banco_tercero_id IS
    'Entidad financiera donde este tercero tiene su cuenta. FK a un tercero con rol BANCO.'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
-- ── Emparejamiento automático: solo coincidencia exacta ─────────────────────
-- Los demás quedan NULL para revisión. Es texto libre: va a haber variantes.
UPDATE cuenta_bancaria cb
   SET tercero_id = t.id
  FROM tercero t
  JOIN tercero_rol tr ON tr.tercero_id = t.id AND tr.rol = 'BANCO'
 WHERE cb.tercero_id IS NULL
   AND cb.banco IS NOT NULL
   AND t.deleted_at IS NULL
   AND t.empresa_id = cb.empresa_id
   AND upper(trim(t.razon_social)) = upper(trim(cb.banco))
MIG_SQL);
    }

    public function down(): void
    {
        // Migración de datos/columnas (ver up). Reversa no automática.
    }
};
