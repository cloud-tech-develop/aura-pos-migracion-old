<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V210 — Nómina R2: vinculación ampliada y migración de contratos.
 *
 * Traducción de la migración Flyway V210__nomina_vinculacion_ampliada.sql (aura-back-old).
 * Cada sentencia en su propio DB::statement. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS codigo VARCHAR(20)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS tipo_vinculacion_id BIGINT REFERENCES nomina_tipo_vinculacion(id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS cargo_id BIGINT REFERENCES nomina_cargo(id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS grupo_nomina_id BIGINT REFERENCES nomina_catalogo(id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS calendario_id BIGINT REFERENCES nomina_catalogo(id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS riesgo_arl_id BIGINT REFERENCES nomina_catalogo(id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS tipo_remuneracion VARCHAR(20) NOT NULL DEFAULT 'MENSUAL'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS horas_semana NUMERIC(5,2)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS temporal BOOLEAN NOT NULL DEFAULT FALSE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS fecha_fin_prueba DATE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS primer_empleo BOOLEAN NOT NULL DEFAULT FALSE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS sucursal_id INTEGER REFERENCES sucursal(id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS area VARCHAR(100)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS municipio_trabajo VARCHAR(120)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS direccion_trabajo VARCHAR(200)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS telefono_trabajo VARCHAR(30)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS jefe_directo_id BIGINT REFERENCES empleados(id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS evaluador_id BIGINT REFERENCES empleados(id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS en_extranjero BOOLEAN NOT NULL DEFAULT FALSE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS doc_interno_numero VARCHAR(50)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS doc_interno_fecha DATE
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS doc_interno_url VARCHAR(500)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD COLUMN IF NOT EXISTS doc_externo_url VARCHAR(500)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral DROP CONSTRAINT IF EXISTS ck_contrato_tipo_remuneracion
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
ALTER TABLE contrato_laboral ADD CONSTRAINT ck_contrato_tipo_remuneracion
    CHECK (tipo_remuneracion IN ('MENSUAL', 'INTEGRAL', 'JORNAL', 'HORA'))
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_contrato_laboral_codigo
    ON contrato_laboral (empresa_id, codigo) WHERE deleted_at IS NULL AND codigo IS NOT NULL
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_contrato_laboral_cargo ON contrato_laboral (cargo_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
CREATE INDEX IF NOT EXISTS idx_contrato_laboral_tipo_vinc ON contrato_laboral (tipo_vinculacion_id)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE contrato_laboral SET tipo_remuneracion = 'INTEGRAL'
 WHERE es_salario_integral = TRUE AND tipo_remuneracion = 'MENSUAL'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE contrato_laboral c SET codigo = x.codigo
  FROM (SELECT id, 'VIN-' || LPAD(ROW_NUMBER() OVER (PARTITION BY empresa_id ORDER BY id)::TEXT, 5, '0') AS codigo
          FROM contrato_laboral WHERE codigo IS NULL AND deleted_at IS NULL) x
 WHERE c.id = x.id
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO nomina_tipo_vinculacion (empresa_id, codigo, nombre, tipo_contrato, tipo_remuneracion,
       frecuencia_pago, exige_fecha_fin, unica_por_empleado, activo, created_at)
SELECT e.empresa_id, s.codigo, s.nombre, s.contrato, s.remuneracion, s.frecuencia, s.exige_fin, TRUE, TRUE, now()
  FROM (SELECT DISTINCT empresa_id FROM contrato_laboral WHERE deleted_at IS NULL) e
 CROSS JOIN (VALUES
       ('PLANTA',   'Planta (término indefinido)', 'INDEFINIDO',  'MENSUAL',  'MENSUAL',   FALSE),
       ('FIJO',     'Término fijo',                'FIJO',        'MENSUAL',  'MENSUAL',   TRUE),
       ('OBRA',     'Obra o labor',                'OBRA_LABOR',  'MENSUAL',  'MENSUAL',   TRUE),
       ('INTEGRAL', 'Salario integral',            'INDEFINIDO',  'INTEGRAL', 'MENSUAL',   FALSE),
       ('APRENDIZ', 'Aprendiz SENA',               'APRENDIZAJE', 'MENSUAL',  'MENSUAL',   TRUE),
       ('JORNAL',   'Por días (jornal)',           'OBRA_LABOR',  'JORNAL',   'QUINCENAL', TRUE)
     ) AS s(codigo, nombre, contrato, remuneracion, frecuencia, exige_fin)
 WHERE NOT EXISTS (SELECT 1 FROM nomina_tipo_vinculacion t
                    WHERE t.empresa_id = e.empresa_id AND t.codigo = s.codigo AND t.deleted_at IS NULL)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE contrato_laboral c SET tipo_vinculacion_id = t.id
  FROM nomina_tipo_vinculacion t
 WHERE c.tipo_vinculacion_id IS NULL AND c.deleted_at IS NULL
   AND t.empresa_id = c.empresa_id AND t.deleted_at IS NULL
   AND t.codigo = CASE
         WHEN c.tipo_remuneracion = 'INTEGRAL' THEN 'INTEGRAL'
         WHEN c.tipo_contrato = 'INDEFINIDO' THEN 'PLANTA'
         WHEN c.tipo_contrato = 'FIJO' THEN 'FIJO'
         WHEN c.tipo_contrato = 'OBRA_LABOR' THEN 'OBRA'
         WHEN c.tipo_contrato = 'APRENDIZAJE' THEN 'APRENDIZ'
       END
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO nomina_cargo (empresa_id, codigo, nombre, activo, created_at)
SELECT x.empresa_id,
       'MIG-' || LPAD(x.n::TEXT, 3, '0'),
       x.nombre, TRUE, now()
  FROM (SELECT empresa_id, nombre, ROW_NUMBER() OVER (PARTITION BY empresa_id ORDER BY nombre) AS n
          FROM (SELECT DISTINCT c.empresa_id, TRIM(c.cargo) AS nombre
                  FROM contrato_laboral c
                 WHERE c.deleted_at IS NULL AND c.cargo IS NOT NULL AND TRIM(c.cargo) <> ''
                   AND NOT EXISTS (SELECT 1 FROM nomina_cargo k
                                    WHERE k.empresa_id = c.empresa_id AND k.deleted_at IS NULL
                                      AND LOWER(k.nombre) = LOWER(TRIM(c.cargo)))) d) x
 WHERE NOT EXISTS (SELECT 1 FROM nomina_cargo k
                    WHERE k.empresa_id = x.empresa_id AND k.deleted_at IS NULL
                      AND k.codigo = 'MIG-' || LPAD(x.n::TEXT, 3, '0'))
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE contrato_laboral c SET cargo_id = k.id
  FROM nomina_cargo k
 WHERE c.cargo_id IS NULL AND c.deleted_at IS NULL
   AND k.empresa_id = c.empresa_id AND k.deleted_at IS NULL
   AND LOWER(k.nombre) = LOWER(TRIM(c.cargo))
MIG_SQL);
    }

    public function down(): void
    {
        // Sin reversa: los datos migrados no se deshacen.
    }
};
