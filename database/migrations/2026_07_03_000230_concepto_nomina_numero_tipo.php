<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V230 — Concepto de nómina ampliado: número visible, tipo, entrada, redondeo,
 * frecuencia, grupo, art. 383 y clase NO_IMPUTABLE (docs/PLAN_CONCEPTOS_CARGOS_NOMINA.md, F1).
 * Limpia antes los conceptos globales duplicados (reapunta sus referencias).
 * 
 * Traducción de la migración Flyway V230__concepto_nomina_numero_tipo.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
CREATE TABLE IF NOT EXISTS nomina_concepto_tipo (
    id      INT          PRIMARY KEY,
    nombre  VARCHAR(100) NOT NULL,
    clase   VARCHAR(20)  NOT NULL,
    activo  BOOLEAN      NOT NULL DEFAULT TRUE
)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO nomina_concepto_tipo (id, nombre, clase) VALUES
    (1,  'Sueldo',                                  'DEVENGADO'),
    (2,  'Auxilio de transporte',                   'DEVENGADO'),
    (3,  'Hora extra diurna',                       'DEVENGADO'),
    (4,  'Hora extra nocturna',                     'DEVENGADO'),
    (5,  'Hora extra dominical/festiva diurna',     'DEVENGADO'),
    (6,  'Hora extra dominical/festiva nocturna',   'DEVENGADO'),
    (7,  'Recargo nocturno',                        'DEVENGADO'),
    (8,  'Recargo dominical/festivo diurno',        'DEVENGADO'),
    (9,  'Recargo dominical/festivo nocturno',      'DEVENGADO'),
    (10, 'Incapacidad',                             'DEVENGADO'),
    (11, 'Licencia remunerada',                     'DEVENGADO'),
    (12, 'Licencia de maternidad/paternidad',       'DEVENGADO'),
    (13, 'Licencia no remunerada',                  'NO_IMPUTABLE'),
    (14, 'Suspensión',                              'NO_IMPUTABLE'),
    (15, 'Ausentismo no remunerado',                'NO_IMPUTABLE'),
    (16, 'Vacaciones',                              'DEVENGADO'),
    (17, 'Prima de servicios',                      'DEVENGADO'),
    (18, 'Cesantías',                               'DEVENGADO'),
    (19, 'Intereses de cesantías',                  'DEVENGADO'),
    (20, 'Bonificación salarial',                   'DEVENGADO'),
    (21, 'Bonificación no salarial',                'DEVENGADO'),
    (22, 'Comisión',                                'DEVENGADO'),
    (23, 'Auxilio',                                 'DEVENGADO'),
    (24, 'Apoyo de sostenimiento (aprendiz)',       'DEVENGADO'),
    (25, 'Otro devengado',                          'DEVENGADO'),
    (26, 'Retroactivo',                             'DEVENGADO'),
    (27, 'Indemnización',                           'DEVENGADO'),
    (28, 'Bonificación por retiro',                 'DEVENGADO'),
    (40, 'Salud empleado',                          'DEDUCCION'),
    (41, 'Pensión empleado',                        'DEDUCCION'),
    (42, 'Fondo de solidaridad pensional',          'DEDUCCION'),
    (43, 'Retención en la fuente (procedimiento 1)','DEDUCCION'),
    (44, 'Retención en la fuente (procedimiento 2)','DEDUCCION'),
    (45, 'Embargo por alimentos',                   'DEDUCCION'),
    (46, 'Embargo judicial',                        'DEDUCCION'),
    (47, 'Embargo cooperativa',                     'DEDUCCION'),
    (48, 'Libranza',                                'DEDUCCION'),
    (49, 'Cooperativa',                             'DEDUCCION'),
    (50, 'Fondo de empleados',                      'DEDUCCION'),
    (51, 'Otros descuentos',                        'DEDUCCION'),
    (52, 'Pensión voluntaria',                      'DEDUCCION'),
    (53, 'Ahorro AFC',                              'DEDUCCION'),
    (54, 'Cuota sindical',                          'DEDUCCION'),
    (55, 'Anticipo o préstamo',                     'DEDUCCION'),
    (60, 'Aporte salud empleador',                  'APORTE_EMPLEADOR'),
    (61, 'Aporte pensión empleador',                'APORTE_EMPLEADOR'),
    (62, 'Caja de compensación',                    'APORTE_EMPLEADOR'),
    (63, 'ICBF',                                    'APORTE_EMPLEADOR'),
    (64, 'SENA',                                    'APORTE_EMPLEADOR'),
    (65, 'ARL',                                     'APORTE_EMPLEADOR'),
    (70, 'Provisión prima',                         'PROVISION'),
    (71, 'Provisión cesantías',                     'PROVISION'),
    (72, 'Provisión intereses de cesantías',        'PROVISION'),
    (73, 'Provisión vacaciones',                    'PROVISION')
ON CONFLICT (id) DO NOTHING
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD COLUMN IF NOT EXISTS numero INT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD COLUMN IF NOT EXISTS tipo_id INT REFERENCES nomina_concepto_tipo(id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD COLUMN IF NOT EXISTS entrada VARCHAR(12) NOT NULL DEFAULT 'NINGUNA'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD COLUMN IF NOT EXISTS redondeo_precision VARCHAR(10) NOT NULL DEFAULT 'NINGUNO'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD COLUMN IF NOT EXISTS redondeo_modo VARCHAR(10) NOT NULL DEFAULT 'AUTOMATICO'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD COLUMN IF NOT EXISTS frecuencia VARCHAR(12) NOT NULL DEFAULT 'CADA_PAGO'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD COLUMN IF NOT EXISTS grupo_id BIGINT REFERENCES nomina_catalogo(id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD COLUMN IF NOT EXISTS aplica_art383 BOOLEAN NOT NULL DEFAULT FALSE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD COLUMN IF NOT EXISTS de_ley BOOLEAN NOT NULL DEFAULT FALSE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD COLUMN IF NOT EXISTS descripcion TEXT
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina DROP CONSTRAINT IF EXISTS chk_cn_clase
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD CONSTRAINT chk_cn_clase CHECK (clase IN
    ('DEVENGADO', 'DEDUCCION', 'APORTE_EMPLEADOR', 'PROVISION', 'NO_IMPUTABLE'))
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina DROP CONSTRAINT IF EXISTS chk_cn_entrada
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD CONSTRAINT chk_cn_entrada CHECK (entrada IN
    ('NINGUNA', 'VALOR', 'CANTIDAD', 'PORCENTAJE'))
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina DROP CONSTRAINT IF EXISTS chk_cn_redondeo
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD CONSTRAINT chk_cn_redondeo CHECK (
    redondeo_precision IN ('NINGUNO', 'ENTERO', 'DECENA', 'CENTENA', 'MIL')
    AND redondeo_modo IN ('AUTOMATICO', 'ARRIBA', 'ABAJO'))
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina DROP CONSTRAINT IF EXISTS chk_cn_frecuencia
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ADD CONSTRAINT chk_cn_frecuencia CHECK (frecuencia IN
    ('CADA_PAGO', 'MENSUAL', 'SEMESTRAL', 'ANUAL', 'LIQUIDACION'))
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE nomina_detalle d
   SET concepto_id = m.keep
  FROM (SELECT id, MIN(id) OVER (PARTITION BY codigo, vigente_desde) AS keep
          FROM concepto_nomina WHERE empresa_id IS NULL) m
 WHERE d.concepto_id = m.id AND m.id <> m.keep
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE liquidacion_prestacion_detalle d
   SET concepto_id = m.keep
  FROM (SELECT id, MIN(id) OVER (PARTITION BY codigo, vigente_desde) AS keep
          FROM concepto_nomina WHERE empresa_id IS NULL) m
 WHERE d.concepto_id = m.id AND m.id <> m.keep
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO concepto_nomina_base (concepto_id, concepto_base_id)
SELECT COALESCE(mc.keep, b.concepto_id), COALESCE(mb.keep, b.concepto_base_id)
  FROM concepto_nomina_base b
  LEFT JOIN (SELECT id, MIN(id) OVER (PARTITION BY codigo, vigente_desde) AS keep
               FROM concepto_nomina WHERE empresa_id IS NULL) mc ON mc.id = b.concepto_id
  LEFT JOIN (SELECT id, MIN(id) OVER (PARTITION BY codigo, vigente_desde) AS keep
               FROM concepto_nomina WHERE empresa_id IS NULL) mb ON mb.id = b.concepto_base_id
 WHERE COALESCE(mc.keep, b.concepto_id) <> COALESCE(mb.keep, b.concepto_base_id)
ON CONFLICT DO NOTHING
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
DELETE FROM concepto_nomina c
 USING (SELECT id, MIN(id) OVER (PARTITION BY codigo, vigente_desde) AS keep
          FROM concepto_nomina WHERE empresa_id IS NULL) m
 WHERE c.id = m.id AND m.id <> m.keep
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_concepto_codigo
    ON concepto_nomina (COALESCE(empresa_id, 0), codigo, vigente_desde)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE concepto_nomina c
   SET numero = m.numero, tipo_id = m.tipo_id, de_ley = TRUE
  FROM (VALUES
       ('SALARIO',                1,   1),
       ('AUX_TRANSP',             2,   2),
       ('HORA_EXTRA_DIURNA',      10,  3),
       ('HORA_EXTRA_NOCTURNA',    11,  4),
       ('HORA_EXTRA_DOMINICAL',   12,  5),
       ('HORA_EXTRA_FESTIVO',     13,  5),
       ('INCAPACIDAD',            20,  10),
       ('LICENCIA_REMUNERADA',    21,  11),
       ('LICENCIA_MATERNIDAD',    22,  12),
       ('BONO',                   30,  21),
       ('COMISION',               31,  22),
       ('OTRO_DEVENGO',           32,  25),
       ('RETROACTIVO',            33,  26),
       ('VACACIONES',             40,  16),
       ('PRIMA',                  41,  17),
       ('CESANTIAS',              42,  18),
       ('INT_CESANTIAS',          43,  19),
       ('INDEMNIZACION',          44,  27),
       ('BONIF_RETIRO',           45,  28),
       ('DED_SALUD',              100, 40),
       ('DED_PENSION',            101, 41),
       ('DED_FONDO_SOLIDARIDAD',  102, 42),
       ('RETEFUENTE_P1',          103, 43),
       ('RETEFUENTE_P2',          104, 44),
       ('EMB_ALIMENTOS',          110, 45),
       ('EMB_JUDICIAL',           111, 46),
       ('EMB_COOP',               112, 47),
       ('DED_OTROS',              120, 51),
       ('APO_SALUD',              200, 60),
       ('APO_PENSION',            201, 61),
       ('APO_CCF',                202, 62),
       ('APO_ICBF',               203, 63),
       ('APO_SENA',               204, 64),
       ('APO_ARL',                205, 65),
       ('PRO_PRIMA',              300, 70),
       ('PRO_CESANTIAS',          301, 71),
       ('PRO_INT_CES',            302, 72),
       ('PRO_VACAC',              303, 73)
       ) AS m(codigo, numero, tipo_id)
 WHERE c.codigo = m.codigo AND c.numero IS NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE concepto_nomina c
   SET numero = n.numero
  FROM (SELECT empresa_id, codigo,
               999 + DENSE_RANK() OVER (PARTITION BY empresa_id ORDER BY MIN(id)) AS numero
          FROM concepto_nomina
         WHERE numero IS NULL
         GROUP BY empresa_id, codigo) n
 WHERE c.numero IS NULL
   AND c.codigo = n.codigo
   AND c.empresa_id IS NOT DISTINCT FROM n.empresa_id
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE concepto_nomina ALTER COLUMN numero SET NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_concepto_numero
    ON concepto_nomina (COALESCE(empresa_id, 0), numero, vigente_desde)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_cn_tipo ON concepto_nomina (tipo_id)
MIG_SQL);
    }

    public function down(): void
    {
        // Sin reversa: migración aditiva sobre datos en uso.
    }
};
