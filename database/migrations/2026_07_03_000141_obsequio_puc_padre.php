<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V141 — reparar la jerarquía de las cuentas sembradas por la V140.
 *
 * Traducción de la migración Flyway V141__obsequio_puc_padre.sql (aura-back-old).
 *
 * En el backend la V140 original insertó las cinco cuentas en un solo INSERT
 * resolviendo `padre_id` con una subconsulta, y cuatro quedaron con padre NULL
 * (las filas de un mismo INSERT no se ven entre sí). Aquí la 140 ya se escribió
 * corregida, así que estos UPDATE no encontrarán nada que arreglar — la
 * migración existe para que la numeración siga espejando al backend y para
 * cubrir cualquier BD que venga del estado malo. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Nivel 2 → clase 5
        DB::statement(<<<'MIG_SQL'
UPDATE plan_cuenta hijo
   SET padre_id = padre.id
  FROM plan_cuenta padre
 WHERE hijo.codigo     = '52'
   AND hijo.padre_id   IS NULL
   AND padre.empresa_id = hijo.empresa_id
   AND padre.codigo     = '5'
MIG_SQL);

        // Nivel 3 → 52
        DB::statement(<<<'MIG_SQL'
UPDATE plan_cuenta hijo
   SET padre_id = padre.id
  FROM plan_cuenta padre
 WHERE hijo.codigo     IN ('5235', '5295')
   AND hijo.padre_id   IS NULL
   AND padre.empresa_id = hijo.empresa_id
   AND padre.codigo     = '52'
MIG_SQL);

        // Nivel 4 → su grupo
        DB::statement(<<<'MIG_SQL'
UPDATE plan_cuenta hijo
   SET padre_id = padre.id
  FROM plan_cuenta padre
 WHERE hijo.codigo     = '523550'
   AND hijo.padre_id   IS NULL
   AND padre.empresa_id = hijo.empresa_id
   AND padre.codigo     = '5235'
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
UPDATE plan_cuenta hijo
   SET padre_id = padre.id
  FROM plan_cuenta padre
 WHERE hijo.codigo     = '529505'
   AND hijo.padre_id   IS NULL
   AND padre.empresa_id = hijo.empresa_id
   AND padre.codigo     = '5295'
MIG_SQL);

        // Red de seguridad: completar cuentas faltantes, nivel por nivel.
        DB::statement(<<<'MIG_SQL'
INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
SELECT e.id, '52', 'Gastos Operacionales de Ventas', 'GASTO', 'DEBITO', 2,
       (SELECT p.id FROM plan_cuenta p WHERE p.empresa_id = e.id AND p.codigo = '5' LIMIT 1),
       TRUE, FALSE, CURRENT_TIMESTAMP
  FROM empresa e
 WHERE EXISTS     (SELECT 1 FROM plan_cuenta p WHERE p.empresa_id = e.id AND p.codigo = '5')
   AND NOT EXISTS (SELECT 1 FROM plan_cuenta p WHERE p.empresa_id = e.id AND p.codigo = '52')
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
SELECT e.id, v.codigo, v.nombre, 'GASTO', 'DEBITO', 3,
       (SELECT p.id FROM plan_cuenta p WHERE p.empresa_id = e.id AND p.codigo = '52' LIMIT 1),
       TRUE, TRUE, CURRENT_TIMESTAMP
  FROM empresa e
  CROSS JOIN (VALUES ('5235', 'Servicios'), ('5295', 'Diversos')) AS v(codigo, nombre)
 WHERE EXISTS     (SELECT 1 FROM plan_cuenta p WHERE p.empresa_id = e.id AND p.codigo = '52')
   AND NOT EXISTS (SELECT 1 FROM plan_cuenta p WHERE p.empresa_id = e.id AND p.codigo = v.codigo)
MIG_SQL);

        DB::statement(<<<'MIG_SQL'
INSERT INTO plan_cuenta (empresa_id, codigo, nombre, tipo, naturaleza, nivel, padre_id, activa, auxiliar, created_at)
SELECT e.id, v.codigo, v.nombre, 'GASTO', 'DEBITO', 4,
       (SELECT p.id FROM plan_cuenta p WHERE p.empresa_id = e.id AND p.codigo = v.codigo_padre LIMIT 1),
       TRUE, TRUE, CURRENT_TIMESTAMP
  FROM empresa e
  CROSS JOIN (VALUES
        ('523550', 'Publicidad Propaganda y Promocion',   '5235'),
        ('529505', 'IVA Asumido en Retiro de Inventario', '5295')
  ) AS v(codigo, nombre, codigo_padre)
 WHERE EXISTS     (SELECT 1 FROM plan_cuenta p WHERE p.empresa_id = e.id AND p.codigo = v.codigo_padre)
   AND NOT EXISTS (SELECT 1 FROM plan_cuenta p WHERE p.empresa_id = e.id AND p.codigo = v.codigo)
MIG_SQL);
    }

    public function down(): void
    {
        // Reparación de datos (ver up). Reversa no automática.
    }
};
