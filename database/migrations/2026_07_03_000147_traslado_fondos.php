<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V147 — traslado de fondos entre cuentas de efectivo.
 *
 * Traducción de la migración Flyway V147__traslado_fondos.sql (aura-back-old).
 *
 * Mover plata de un bolsillo a otro de la propia empresa no es ni un gasto ni
 * una compra: no hay tercero, no hay resultado, solo cambia dónde está el
 * dinero. Hasta ahora no existía ningún documento que lo representara, así que
 * operaciones cotidianas se registraban disfrazadas de otra cosa:
 *
 *   · constituir la caja menor        → se colaba como "gasto"
 *   · consignar el efectivo del día   → no se registraba, o se ajustaba a mano
 *   · pasar plata entre dos bancos    → dos movimientos sueltos sin relación
 *
 * Un solo documento con origen y destino cubre los tres. El asiento es siempre
 * el mismo: DÉBITO la cuenta destino, CRÉDITO la cuenta origen.
 *
 * El caso que motivó la tabla es la CAJA MENOR. Con ella el administrador de
 * fondos deja de competir por la caja del cajero:
 *   1. Constituye:  DB 110505 Caja Menor / CR 1105 Caja  ó  1110 Bancos
 *   2. Gasta:       DB 5xxx Gasto        / CR 110505      ← no toca el arqueo
 *   3. Reembolsa:   DB 110505            / CR 1110 Bancos
 * El paso 2 no necesita documento nuevo: es un gasto normal eligiendo la caja
 * menor como cuenta de pago (V142 + V146).
 *
 * IMPORTANTE: el backend corre con ddl-auto=validate, así que no arranca hasta
 * que esta migración se aplique. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS traslado_fondos (
                id          BIGSERIAL PRIMARY KEY,
                empresa_id  INT           NOT NULL,
                sucursal_id INT,
                fecha       DATE          NOT NULL,
                monto       NUMERIC(15,2) NOT NULL,

                -- Origen: de dónde sale. CAJA | BANCO | CUENTA
                -- Solo se informa el identificador que corresponde al tipo; los
                -- otros dos quedan en NULL. La coherencia la valida el servicio,
                -- que además resuelve la cuenta contable de cada extremo con
                -- ResolucionCuentaPago.
                origen_tipo            VARCHAR(20) NOT NULL,
                origen_turno_caja_id   BIGINT,
                origen_cuenta_banco_id BIGINT,
                origen_cuenta_id       BIGINT,

                -- Destino: a dónde entra
                destino_tipo            VARCHAR(20) NOT NULL,
                destino_turno_caja_id   BIGINT,
                destino_cuenta_banco_id BIGINT,
                destino_cuenta_id       BIGINT,

                -- Para qué se movió la plata. Solo etiqueta: no cambia el
                -- asiento, pero permite listar "reembolsos de caja menor del
                -- mes" sin leer observaciones.
                -- CONSTITUCION_CAJA_MENOR | REEMBOLSO_CAJA_MENOR | CONSIGNACION | TRASLADO
                concepto    VARCHAR(40)  NOT NULL DEFAULT 'TRASLADO',
                observacion VARCHAR(500),

                -- Quién responde por el fondo en destino (el administrador de
                -- fondos de la caja menor). Distinto de usuario_id, que es quien
                -- digitó el documento.
                responsable_id INT,
                usuario_id     INT         NOT NULL,
                estado         VARCHAR(20) NOT NULL DEFAULT 'CONFIRMADO',
                created_at     TIMESTAMP   NOT NULL DEFAULT now()
            )
        SQL);

        $checks = [
            'chk_traslado_fondos_monto'        => 'CHECK (monto > 0)',
            'chk_traslado_fondos_origen_tipo'  => "CHECK (origen_tipo IN ('CAJA', 'BANCO', 'CUENTA'))",
            'chk_traslado_fondos_destino_tipo' => "CHECK (destino_tipo IN ('CAJA', 'BANCO', 'CUENTA'))",
            'chk_traslado_fondos_estado'       => "CHECK (estado IN ('CONFIRMADO', 'ANULADO'))",
        ];
        foreach ($checks as $nombre => $definicion) {
            DB::statement("ALTER TABLE traslado_fondos DROP CONSTRAINT IF EXISTS {$nombre}");
            DB::statement("ALTER TABLE traslado_fondos ADD CONSTRAINT {$nombre} {$definicion}");
        }

        $fks = [
            'fk_traslado_fondos_empresa'        => 'FOREIGN KEY (empresa_id) REFERENCES empresa(id)',
            'fk_traslado_fondos_sucursal'       => 'FOREIGN KEY (sucursal_id) REFERENCES sucursal(id)',
            'fk_traslado_fondos_origen_turno'   => 'FOREIGN KEY (origen_turno_caja_id) REFERENCES turno_caja(id)',
            'fk_traslado_fondos_destino_turno'  => 'FOREIGN KEY (destino_turno_caja_id) REFERENCES turno_caja(id)',
            'fk_traslado_fondos_origen_banco'   => 'FOREIGN KEY (origen_cuenta_banco_id) REFERENCES cuenta_bancaria(id)',
            'fk_traslado_fondos_destino_banco'  => 'FOREIGN KEY (destino_cuenta_banco_id) REFERENCES cuenta_bancaria(id)',
            'fk_traslado_fondos_origen_cuenta'  => 'FOREIGN KEY (origen_cuenta_id) REFERENCES plan_cuenta(id)',
            'fk_traslado_fondos_destino_cuenta' => 'FOREIGN KEY (destino_cuenta_id) REFERENCES plan_cuenta(id)',
            'fk_traslado_fondos_usuario'        => 'FOREIGN KEY (usuario_id) REFERENCES usuario(id)',
            'fk_traslado_fondos_responsable'    => 'FOREIGN KEY (responsable_id) REFERENCES usuario(id)',
        ];
        foreach ($fks as $nombre => $definicion) {
            DB::statement("ALTER TABLE traslado_fondos DROP CONSTRAINT IF EXISTS {$nombre}");
            DB::statement("ALTER TABLE traslado_fondos ADD CONSTRAINT {$nombre} {$definicion}");
        }

        DB::statement('CREATE INDEX IF NOT EXISTS idx_traslado_fondos_empresa_fecha ON traslado_fondos (empresa_id, fecha DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_traslado_fondos_concepto      ON traslado_fondos (empresa_id, concepto)');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS traslado_fondos');
    }
};
