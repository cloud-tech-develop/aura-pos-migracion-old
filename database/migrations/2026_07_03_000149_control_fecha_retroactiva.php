<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V149 — control de documentos con fecha retroactiva.
 *
 * Traducción de la migración Flyway V149__control_fecha_retroactiva.sql
 * (aura-back-old).
 *
 * Las fases anteriores le dieron al usuario la forma de declarar de dónde sale
 * la plata, y al cajero la forma de ver qué parte de su arqueo no es suya. Falta
 * el freno: hoy nada impide cargar una factura de hace tres semanas a la caja de
 * hoy, y con eso vuelve el descuadre que todo esto vino a evitar.
 *
 * La restricción aplica SOLO a la vía CAJA. Las demás — crédito, banco, caja
 * menor — no descuadran el arqueo de nadie, así que no tienen por qué pedir
 * permiso: ponerles fricción solo empujaría al usuario de vuelta a "Caja", que
 * es exactamente lo contrario de lo que se busca.
 *
 * Dentro de la ventana de gracia no hay fricción alguna: pagar hoy la factura de
 * ayer es la operación más normal del mundo.
 *
 * IMPORTANTE: el backend corre con ddl-auto=validate, así que no arranca hasta
 * que esta migración se aplique. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Parámetros por empresa ──────────────────────────────────────────
        // Viven en `empresa` como el resto de la configuración operativa
        // (modo_contabilizacion, factura_electronica…), no en una tabla aparte.

        // Días hacia atrás que se aceptan sin explicación. 3 cubre el fin de
        // semana, que es cuando más se acumulan facturas sin digitar.
        DB::statement('ALTER TABLE empresa ADD COLUMN IF NOT EXISTS dias_gracia_documento_retroactivo INT NOT NULL DEFAULT 3');

        // Con esto activo, pasada la ventana el documento NO puede ir a la caja
        // salvo que lo autorice el rol de abajo. Apagarlo deja pasar con motivo
        // obligatorio.
        DB::statement('ALTER TABLE empresa ADD COLUMN IF NOT EXISTS bloquear_caja_retroactiva BOOLEAN NOT NULL DEFAULT TRUE');

        // Rol que puede saltarse la ventana. Se compara contra el rol del token.
        DB::statement("ALTER TABLE empresa ADD COLUMN IF NOT EXISTS rol_autoriza_retroactivo VARCHAR(40) NOT NULL DEFAULT 'ADMIN'");

        DB::statement('ALTER TABLE empresa DROP CONSTRAINT IF EXISTS chk_empresa_dias_gracia');
        DB::statement('ALTER TABLE empresa ADD CONSTRAINT chk_empresa_dias_gracia CHECK (dias_gracia_documento_retroactivo >= 0)');

        // ── Rastro en el documento ──────────────────────────────────────────
        // Quién autorizó y por qué. Sin esto la autorización no sirve de nada:
        // el objetivo no es solo frenar, es poder preguntar después qué pasó.
        foreach (['compra', 'gasto'] as $tabla) {
            DB::statement("ALTER TABLE {$tabla} ADD COLUMN IF NOT EXISTS motivo_retroactivo VARCHAR(500)");
            DB::statement("ALTER TABLE {$tabla} ADD COLUMN IF NOT EXISTS autorizado_por INT");

            DB::statement("ALTER TABLE {$tabla} DROP CONSTRAINT IF EXISTS fk_{$tabla}_autorizado_por");
            DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT fk_{$tabla}_autorizado_por FOREIGN KEY (autorizado_por) REFERENCES usuario(id)");
        }

        // Para el reporte "qué se autorizó fuera de plazo este mes".
        DB::statement('CREATE INDEX IF NOT EXISTS idx_compra_retroactiva ON compra (autorizado_por) WHERE autorizado_por IS NOT NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_gasto_retroactivo  ON gasto  (autorizado_por) WHERE autorizado_por IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_gasto_retroactivo');
        DB::statement('DROP INDEX IF EXISTS idx_compra_retroactiva');

        foreach (['compra', 'gasto'] as $tabla) {
            DB::statement("ALTER TABLE {$tabla} DROP CONSTRAINT IF EXISTS fk_{$tabla}_autorizado_por");
            DB::statement("ALTER TABLE {$tabla} DROP COLUMN IF EXISTS autorizado_por");
            DB::statement("ALTER TABLE {$tabla} DROP COLUMN IF EXISTS motivo_retroactivo");
        }

        DB::statement('ALTER TABLE empresa DROP CONSTRAINT IF EXISTS chk_empresa_dias_gracia');
        DB::statement('ALTER TABLE empresa DROP COLUMN IF EXISTS rol_autoriza_retroactivo');
        DB::statement('ALTER TABLE empresa DROP COLUMN IF EXISTS bloquear_caja_retroactiva');
        DB::statement('ALTER TABLE empresa DROP COLUMN IF EXISTS dias_gracia_documento_retroactivo');
    }
};
