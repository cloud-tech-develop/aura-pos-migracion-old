<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V140 — obsequios (retiro de inventario sin contraprestación).
 *
 * Traducción fiel de la migración Flyway V140__obsequio.sql (aura-back-old):
 * cada sentencia de PostgreSQL va en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
-- Regalar un producto no es una venta con 100% de descuento: no hay ingreso,
-- pero sí sale inventario y sí hay un costo que tiene que aterrizar en algún
-- lado. Hasta hoy la única salida sin venta era la merma, que lo clasificaba
-- como pérdida — mezclando lo que se daña con lo que se regala, dos cosas que
-- el negocio necesita medir por separado.
--
-- El asiento que genera este documento:
--   DB 523550 Obsequios y muestras   ·  CR 1435 Inventario     (por el costo)
-- y, si `genera_iva`:
--   DB 529505 IVA asumido en retiro  ·  CR 240801 IVA generado (por el IVA)

CREATE TABLE IF NOT EXISTS obsequio (
    id                   BIGSERIAL PRIMARY KEY,
    empresa_id           INTEGER      NOT NULL,
    sucursal_id          INTEGER      NOT NULL,
    usuario_id           INTEGER,
    tercero_id           BIGINT,
    fecha                TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motivo               VARCHAR(30)  NOT NULL,
    observacion          VARCHAR(300),
    costo_total          NUMERIC(15,2) NOT NULL DEFAULT 0,
    base_comercial_total NUMERIC(15,2) NOT NULL DEFAULT 0,
    iva_total            NUMERIC(15,2) NOT NULL DEFAULT 0,
    genera_iva           BOOLEAN      NOT NULL DEFAULT TRUE,
    estado               VARCHAR(20)  NOT NULL DEFAULT 'APROBADO',
    created_at           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_obsequio_motivo CHECK (motivo IN
        ('MUESTRA_COMERCIAL', 'PROMOCION', 'CORTESIA_CLIENTE', 'DONACION', 'OTRO')),
    CONSTRAINT chk_obsequio_estado CHECK (estado IN ('APROBADO', 'ANULADO')),
    CONSTRAINT fk_obsequio_empresa  FOREIGN KEY (empresa_id)  REFERENCES empresa(id),
    CONSTRAINT fk_obsequio_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursal(id),
    CONSTRAINT fk_obsequio_tercero  FOREIGN KEY (tercero_id)  REFERENCES tercero(id)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS obsequio_detalle (
    id                        BIGSERIAL PRIMARY KEY,
    obsequio_id               BIGINT        NOT NULL,
    producto_id               BIGINT        NOT NULL,
    lote_id                   BIGINT,
    cantidad                  NUMERIC(18,6) NOT NULL,
    -- Costo unitario congelado al entregar: el asiento no puede moverse si el
    -- costo del producto cambia después.
    costo_unitario            NUMERIC(15,2) NOT NULL DEFAULT 0,
    -- Valor comercial unitario SIN IVA, base del impuesto por retiro.
    base_comercial_unitaria   NUMERIC(15,2) NOT NULL DEFAULT 0,
    iva_valor                 NUMERIC(15,2) NOT NULL DEFAULT 0,

    CONSTRAINT fk_obsequio_detalle_obsequio FOREIGN KEY (obsequio_id) REFERENCES obsequio(id),
    CONSTRAINT fk_obsequio_detalle_producto FOREIGN KEY (producto_id) REFERENCES producto(id),
    CONSTRAINT fk_obsequio_detalle_lote     FOREIGN KEY (lote_id)     REFERENCES lote(id)
)
MIG_SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_obsequio_empresa ON obsequio (empresa_id, fecha)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_obsequio_detalle ON obsequio_detalle (obsequio_id)');

        // Cuentas del PUC que necesita el asiento. Va en tres pasos y no en un
        // solo INSERT porque `padre_id` se resuelve con una subconsulta: las
        // filas de un mismo INSERT no se ven entre sí, así que el nivel N tiene
        // que estar confirmado antes de insertar el N+1.
        DB::statement(<<<'MIG_SQL'
DO $$
DECLARE
    nivel_actual SMALLINT;
BEGIN
    FOREACH nivel_actual IN ARRAY ARRAY[2, 3, 4]::SMALLINT[] LOOP
        INSERT INTO plan_cuenta
               (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
        SELECT e.id, v.codigo, v.nombre, 'GASTO', 'DEBITO', v.nivel,
               (SELECT p.id FROM plan_cuenta p
                 WHERE p.empresa_id = e.id AND p.codigo = v.codigo_padre LIMIT 1),
               TRUE, v.nivel >= 3, CURRENT_TIMESTAMP
          FROM empresa e
          CROSS JOIN (VALUES
                ('52',     'Gastos Operacionales de Ventas',      2::SMALLINT, '5'),
                ('5235',   'Servicios',                           3::SMALLINT, '52'),
                ('5295',   'Diversos',                            3::SMALLINT, '52'),
                ('523550', 'Publicidad Propaganda y Promocion',   4::SMALLINT, '5235'),
                ('529505', 'IVA Asumido en Retiro de Inventario', 4::SMALLINT, '5295')
          ) AS v(codigo, nombre, nivel, codigo_padre)
         WHERE v.nivel = nivel_actual
           AND NOT EXISTS (
                SELECT 1 FROM plan_cuenta p
                 WHERE p.empresa_id = e.id AND p.codigo = v.codigo)
           AND EXISTS (
                SELECT 1 FROM plan_cuenta p
                 WHERE p.empresa_id = e.id AND p.codigo = '5');
    END LOOP;
END $$
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS obsequio_detalle');
        DB::statement('DROP TABLE IF EXISTS obsequio');
        // Las cuentas del PUC no se borran: pueden tener movimientos.
    }
};
