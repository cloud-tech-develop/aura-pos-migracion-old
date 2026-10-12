<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V225 — Exógena DIAN completa: columnas reales por formato, tipo de persona del
 * informante, formatos 1003/1010/1012 y 2276 desde la nómina.
 *
 * Traducción de la migración Flyway V225__exogena_completa.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE empresa ADD COLUMN IF NOT EXISTS tipo_persona VARCHAR(10) NOT NULL DEFAULT 'JURIDICA'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE exogena_formato ADD COLUMN IF NOT EXISTS aplica_a VARCHAR(10) NOT NULL DEFAULT 'AMBAS'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE exogena_formato ADD COLUMN IF NOT EXISTS fuente VARCHAR(15) NOT NULL DEFAULT 'CONTABILIDAD'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE exogena_formato ADD COLUMN IF NOT EXISTS columna_requerida VARCHAR(30)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE exogena_formato ADD COLUMN IF NOT EXISTS cuantia_menor NUMERIC(18,2) NOT NULL DEFAULT 0
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE exogena_formato ADD COLUMN IF NOT EXISTS encabezado VARCHAR(200) NOT NULL DEFAULT 'CPT,TDOC,NID,DV,APL1,APL2,NOM1,NOM2,RAZ,DIR,DPTO,MUN,PAIS'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS exogena_columna (
    id         BIGSERIAL PRIMARY KEY,
    formato_id BIGINT       NOT NULL REFERENCES exogena_formato(id),
    codigo     VARCHAR(30)  NOT NULL,
    nombre     VARCHAR(200) NOT NULL,
    orden      INT          NOT NULL,
    principal  BOOLEAN      NOT NULL DEFAULT FALSE,
    UNIQUE (formato_id, codigo)
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE exogena_mapeo_cuenta ADD COLUMN IF NOT EXISTS columna VARCHAR(30)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE exogena_linea ADD COLUMN IF NOT EXISTS valores JSONB
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO exogena_formato (codigo, nombre, version_dian)
SELECT v.codigo, v.nombre, v.version_dian
FROM (VALUES ('1003', 'Retenciones en la fuente que le practicaron',                  7),
             ('1010', 'Información de socios, accionistas, comuneros y/o cooperados', 8),
             ('1012', 'Declaraciones tributarias, acciones, inversiones, bonos, cuentas de ahorro y corrientes', 7)
     ) AS v(codigo, nombre, version_dian)
WHERE NOT EXISTS (SELECT 1 FROM exogena_formato f WHERE f.codigo = v.codigo)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE exogena_formato SET version_dian = 10, aplica_a = 'AMBAS', fuente = 'CONTABILIDAD', columna_requerida = NULL, cuantia_menor = 100000, encabezado = 'CPT,TDOC,NID,DV,APL1,APL2,NOM1,NOM2,RAZ,DIR,DPTO,MUN,PAIS' WHERE codigo = '1001'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE exogena_formato SET version_dian = 7, aplica_a = 'AMBAS', fuente = 'CONTABILIDAD', columna_requerida = 'RETENCION', cuantia_menor = 0, encabezado = 'CPT,TDOC,NID,DV,APL1,APL2,NOM1,NOM2,RAZ,DIR,DPTO,MUN' WHERE codigo = '1003'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE exogena_formato SET version_dian = 7, aplica_a = 'AMBAS', fuente = 'CONTABILIDAD', columna_requerida = NULL, cuantia_menor = 500000, encabezado = 'TDOC,NID,DV,APL1,APL2,NOM1,NOM2,RAZ' WHERE codigo = '1005'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE exogena_formato SET version_dian = 8, aplica_a = 'AMBAS', fuente = 'CONTABILIDAD', columna_requerida = NULL, cuantia_menor = 500000, encabezado = 'TDOC,NID,DV,APL1,APL2,NOM1,NOM2,RAZ' WHERE codigo = '1006'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE exogena_formato SET version_dian = 9, aplica_a = 'AMBAS', fuente = 'CONTABILIDAD', columna_requerida = NULL, cuantia_menor = 500000, encabezado = 'CPT,TDOC,NID,APL1,APL2,NOM1,NOM2,RAZ,PAIS' WHERE codigo = '1007'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE exogena_formato SET version_dian = 7, aplica_a = 'AMBAS', fuente = 'CONTABILIDAD', columna_requerida = NULL, cuantia_menor = 1000000, encabezado = 'CPT,TDOC,NID,DV,APL1,APL2,NOM1,NOM2,RAZ,DIR,DPTO,MUN,PAIS' WHERE codigo = '1008'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE exogena_formato SET version_dian = 7, aplica_a = 'AMBAS', fuente = 'CONTABILIDAD', columna_requerida = NULL, cuantia_menor = 1000000, encabezado = 'CPT,TDOC,NID,DV,APL1,APL2,NOM1,NOM2,RAZ,DIR,DPTO,MUN,PAIS' WHERE codigo = '1009'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE exogena_formato SET version_dian = 8, aplica_a = 'JURIDICA', fuente = 'CONTABILIDAD', columna_requerida = NULL, cuantia_menor = 0, encabezado = 'TDOC,NID,DV,APL1,APL2,NOM1,NOM2,RAZ,DIR,DPTO,MUN,PAIS' WHERE codigo = '1010'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE exogena_formato SET version_dian = 7, aplica_a = 'AMBAS', fuente = 'CONTABILIDAD', columna_requerida = NULL, cuantia_menor = 0, encabezado = 'CPT,TDOC,NID,DV,APL1,APL2,NOM1,NOM2,RAZ,PAIS' WHERE codigo = '1012'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE exogena_formato SET version_dian = 4, aplica_a = 'AMBAS', fuente = 'NOMINA', columna_requerida = NULL, cuantia_menor = 0, encabezado = 'TDOC,NID,APL1,APL2,NOM1,NOM2,DIR,DPTO,MUN,PAIS' WHERE codigo = '2276'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO exogena_concepto (formato_id, codigo, nombre)
SELECT f.id, v.concepto, v.nombre
FROM (VALUES ('1003', '1301', 'Retención por salarios, prestaciones y demás pagos laborales'),
             ('1003', '1302', 'Retención por ventas'),
             ('1003', '1303', 'Retención por servicios'),
             ('1003', '1304', 'Retención por honorarios'),
             ('1003', '1305', 'Retención por comisiones'),
             ('1003', '1306', 'Retención por intereses y rendimientos financieros'),
             ('1003', '1307', 'Retención por arrendamientos'),
             ('1003', '1308', 'Retención por otros conceptos'),
             ('1003', '1309', 'Retención en la fuente en el impuesto sobre las ventas'),
             ('1010', '1010', 'Socios, accionistas, comuneros y/o cooperados'),
             ('1012', '1110', 'Saldos en cuentas corrientes y/o de ahorro'),
             ('1012', '1115', 'Inversiones: CDT, bonos, títulos y demás'),
             ('1012', '1120', 'Acciones y aportes en sociedades')
     ) AS v(formato, concepto, nombre)
JOIN exogena_formato f ON f.codigo = v.formato
WHERE NOT EXISTS (SELECT 1 FROM exogena_concepto c
                  WHERE c.formato_id = f.id AND c.codigo = v.concepto)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO exogena_columna (formato_id, codigo, nombre, orden, principal)
SELECT f.id, v.codigo, v.nombre, v.orden, v.principal
FROM (VALUES ('1001', 'PAGO_DED', 'Pago o abono en cuenta deducible', 1, TRUE),
             ('1001', 'PAGO_NODED', 'Pago o abono en cuenta no deducible', 2, FALSE),
             ('1001', 'IVA_DED', 'IVA mayor valor del costo o gasto deducible', 3, FALSE),
             ('1001', 'IVA_NODED', 'IVA mayor valor del costo o gasto no deducible', 4, FALSE),
             ('1001', 'RET_RENTA', 'Retención en la fuente practicada en renta', 5, FALSE),
             ('1001', 'RET_ASUMIDA', 'Retención en la fuente asumida en renta', 6, FALSE),
             ('1001', 'RET_IVA_RC', 'Retención en la fuente practicada IVA a responsables del IVA', 7, FALSE),
             ('1001', 'RET_IVA_ND', 'Retención en la fuente practicada IVA a no residentes o no domiciliados', 8, FALSE),
             ('1003', 'BASE', 'Valor acumulado del pago o abono sujeto a retención en la fuente', 1, TRUE),
             ('1003', 'RETENCION', 'Retención que le practicaron', 2, FALSE),
             ('1005', 'IVA_DESC', 'Impuesto descontable', 1, TRUE),
             ('1005', 'IVA_DEVOL', 'IVA resultante por devoluciones en ventas anuladas, rescindidas o resueltas', 2, FALSE),
             ('1006', 'IVA_GEN', 'Impuesto generado', 1, TRUE),
             ('1006', 'IVA_RECUP', 'IVA recuperado en devoluciones en compras anuladas, rescindidas o resueltas', 2, FALSE),
             ('1006', 'IMP_CONSUMO', 'Impuesto al consumo', 3, FALSE),
             ('1007', 'ING_BRUTO', 'Ingresos brutos recibidos', 1, TRUE),
             ('1007', 'DEVOLUCIONES', 'Devoluciones, rebajas y descuentos', 2, FALSE),
             ('1008', 'SALDO', 'Saldo cuentas por cobrar al 31-12', 1, TRUE),
             ('1009', 'SALDO', 'Saldo cuentas por pagar al 31-12', 1, TRUE),
             ('1010', 'VALOR_PATRIMONIAL', 'Valor patrimonial acciones o aportes al 31-12', 1, TRUE),
             ('1010', 'PORCENTAJE', 'Porcentaje de participación', 2, FALSE),
             ('1012', 'VALOR', 'Valor al 31-12', 1, TRUE),
             ('2276', 'SALARIOS', 'Pagos por salarios', 1, TRUE),
             ('2276', 'EMOLUMENTOS', 'Pagos por emolumentos eclesiásticos', 2, FALSE),
             ('2276', 'BONOS_ELECTRONICOS', 'Pagos realizados con bonos electrónicos o de papel de servicio, cheques, tarjetas, vales, etc.', 3, FALSE),
             ('2276', 'EXCESO_ALIMENTACION', 'Valor del exceso de los pagos por alimentación mayores a 41 UVT', 4, FALSE),
             ('2276', 'HONORARIOS', 'Pagos por honorarios', 5, FALSE),
             ('2276', 'SERVICIOS', 'Pagos por servicios', 6, FALSE),
             ('2276', 'COMISIONES', 'Pagos por comisiones', 7, FALSE),
             ('2276', 'PRESTACIONES', 'Pagos por prestaciones sociales', 8, FALSE),
             ('2276', 'VIATICOS', 'Pagos por viáticos', 9, FALSE),
             ('2276', 'GASTOS_REPRESENTACION', 'Pagos por gastos de representación', 10, FALSE),
             ('2276', 'COOPERATIVO', 'Pagos por compensaciones por el trabajo asociado cooperativo', 11, FALSE),
             ('2276', 'OTROS', 'Otros pagos', 12, FALSE),
             ('2276', 'CESANTIAS_PAGADAS', 'Cesantías e intereses de cesantías efectivamente pagadas al empleado', 13, FALSE),
             ('2276', 'CESANTIAS_FONDO', 'Cesantías consignadas al fondo de cesantías', 14, FALSE),
             ('2276', 'CESANTIAS_TRADICIONAL', 'Auxilio de cesantías reconocido a trabajadores del régimen tradicional', 15, FALSE),
             ('2276', 'PENSIONES', 'Pensiones de jubilación, vejez o invalidez', 16, FALSE),
             ('2276', 'TOTAL_BRUTO', 'Total ingresos brutos por rentas de trabajo y pensión', 17, FALSE),
             ('2276', 'APORTE_SALUD', 'Aportes obligatorios por salud a cargo del trabajador', 18, FALSE),
             ('2276', 'APORTE_PENSION', 'Aportes obligatorios a fondos de pensiones y solidaridad pensional a cargo del trabajador', 19, FALSE),
             ('2276', 'APORTE_VOL_RAIS', 'Aportes voluntarios al régimen de ahorro individual con solidaridad - RAIS', 20, FALSE),
             ('2276', 'APORTE_VOL_FONDOS', 'Aportes voluntarios a fondos de pensiones voluntarias', 21, FALSE),
             ('2276', 'APORTE_AFC', 'Aportes a cuentas AFC', 22, FALSE),
             ('2276', 'APORTE_AVC', 'Aportes a cuentas AVC', 23, FALSE),
             ('2276', 'RETENCION', 'Valor de las retenciones en la fuente por pagos de rentas de trabajo o pensiones', 24, FALSE)
     ) AS v(formato, codigo, nombre, orden, principal)
JOIN exogena_formato f ON f.codigo = v.formato
WHERE NOT EXISTS (SELECT 1 FROM exogena_columna c WHERE c.formato_id = f.id AND c.codigo = v.codigo)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO exogena_mapeo_cuenta (empresa_id, concepto_id, cuenta_desde, tipo_valor, columna)
SELECT e.id, c.id, v.desde, v.tipo_valor, v.columna
FROM (VALUES ('1001', '5001', '236505', 'MOVIMIENTO_CR', 'RET_RENTA'),
             ('1001', '5002', '236515', 'MOVIMIENTO_CR', 'RET_RENTA'),
             ('1001', '5003', '236520', 'MOVIMIENTO_CR', 'RET_RENTA'),
             ('1001', '5004', '236525', 'MOVIMIENTO_CR', 'RET_RENTA'),
             ('1001', '5005', '236530', 'MOVIMIENTO_CR', 'RET_RENTA'),
             ('1001', '5006', '236535', 'MOVIMIENTO_CR', 'RET_RENTA'),
             ('1001', '5007', '236540', 'MOVIMIENTO_CR', 'RET_RENTA'),
             ('1001', '5016', '2365', 'MOVIMIENTO_CR', 'RET_RENTA'),
             ('1001', '5016', '2367', 'MOVIMIENTO_CR', 'RET_IVA_RC'),
             ('1003', '1303', '135515', 'MOVIMIENTO_DB', 'RETENCION'),
             ('1003', '1309', '135517', 'MOVIMIENTO_DB', 'RETENCION'),
             ('1003', '1303', '41', 'MOVIMIENTO_CR', 'BASE'),
             ('1007', '4001', '4175', 'MOVIMIENTO_DB', 'DEVOLUCIONES'),
             ('1010', '1010', '3105', 'SALDO_CR', 'VALOR_PATRIMONIAL'),
             ('1010', '1010', '3115', 'SALDO_CR', 'VALOR_PATRIMONIAL'),
             ('1012', '1110', '1110', 'SALDO_DB', 'VALOR'),
             ('1012', '1110', '1120', 'SALDO_DB', 'VALOR'),
             ('1012', '1115', '1225', 'SALDO_DB', 'VALOR')
     ) AS v(formato, concepto, desde, tipo_valor, columna)
JOIN exogena_formato f  ON f.codigo = v.formato
JOIN exogena_concepto c ON c.formato_id = f.id AND c.codigo = v.concepto
CROSS JOIN empresa e
WHERE EXISTS (SELECT 1 FROM exogena_mapeo_cuenta x WHERE x.empresa_id = e.id)
  AND NOT EXISTS (SELECT 1 FROM exogena_mapeo_cuenta m
                  WHERE m.empresa_id = e.id AND m.concepto_id = c.id
                    AND m.cuenta_desde = v.desde
                    AND COALESCE(m.columna, '') = v.columna)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
DELETE FROM exogena_mapeo_cuenta m
 USING exogena_concepto c, exogena_formato f
 WHERE m.concepto_id = c.id AND c.formato_id = f.id AND f.codigo = '2276'
MIG_SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS exogena_columna');
    }
};
