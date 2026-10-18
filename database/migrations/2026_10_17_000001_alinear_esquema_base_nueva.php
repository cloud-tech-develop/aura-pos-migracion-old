<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Deja una base NUEVA (instalación desde cero, p. ej. el servidor local de una
 * tienda del modo híbrido) con el mismo esquema que las bases en uso.
 *
 * Las bases en uso nacieron con Hibernate (ddl-auto=update) antes de Flyway, y
 * Hibernate dejó columnas y tipos que las migraciones no reproducen. Comparado
 * columna por columna contra aura-pos (2026-10-17):
 *   - 5 columnas que ninguna migración agregaba.
 *   - 44 columnas integer/bigint, venta.fecha_emision y el dv de empresa y
 *     tercero con otro tipo. Con ddl-auto=validate el backend no arranca si el
 *     tipo no coincide con la entidad.
 *   - 165 columnas NOT NULL que en las bases en uso aceptan nulos (y 3 al revés):
 *     los datos de la nube no entraban en el nodo.
 *
 * Cada cambio se aplica solo si la columna falta o es distinta: en las bases
 * en uso (aura-pos, producción) esta migración no hace nada.
 */
return new class extends Migration
{
    /** Tabla, columna y tipo que tienen las bases en uso. */
    private const TIPOS = [
        ['caja', 'sucursal_id', 'integer'],
        ['categoria', 'empresa_id', 'integer'],
        ['comision_config', 'categoria_id', 'bigint'],
        ['compra', 'empresa_id', 'integer'],
        ['compra', 'sucursal_id', 'integer'],
        ['compra', 'usuario_id', 'integer'],
        ['cotizacion', 'turno_caja_id', 'bigint'],
        ['descuento_cliente', 'categoria_id', 'bigint'],
        ['descuento_cliente', 'tercero_id', 'bigint'],
        ['empresa', 'id', 'integer'],
        ['historial_sesion', 'id', 'integer'],
        ['historial_sesion', 'sucursal_id', 'integer'],
        ['historial_sesion', 'usuario_id', 'integer'],
        ['inventario', 'sucursal_id', 'integer'],
        ['lista_precios', 'empresa_id', 'integer'],
        ['lote', 'sucursal_id', 'integer'],
        ['marca', 'empresa_id', 'integer'],
        ['merma', 'empresa_id', 'integer'],
        ['merma', 'sucursal_id', 'integer'],
        ['merma', 'usuario_id', 'integer'],
        ['motivo_merma', 'empresa_id', 'integer'],
        ['movimiento_inventario', 'sucursal_id', 'integer'],
        ['precio_cliente', 'producto_presentacion_id', 'bigint'],
        ['precio_cliente', 'tercero_id', 'bigint'],
        ['precio_volumen', 'producto_presentacion_id', 'bigint'],
        ['producto', 'empresa_id', 'integer'],
        ['regla_descuento', 'empresa_id', 'integer'],
        ['serial_producto', 'sucursal_id', 'integer'],
        ['sucursal', 'empresa_id', 'integer'],
        ['sucursal', 'id', 'integer'],
        ['tercero', 'empresa_id', 'integer'],
        ['traslado', 'empresa_id', 'integer'],
        ['traslado', 'sucursal_destino_id', 'integer'],
        ['traslado', 'sucursal_origen_id', 'integer'],
        ['traslado', 'usuario_id', 'integer'],
        ['turno_caja', 'usuario_id', 'integer'],
        ['usuario', 'empresa_id', 'integer'],
        ['usuario', 'id', 'integer'],
        ['usuario_sucursal', 'id', 'integer'],
        ['usuario_sucursal', 'sucursal_id', 'integer'],
        ['usuario_sucursal', 'usuario_id', 'integer'],
        ['venta', 'empresa_id', 'integer'],
        ['venta', 'sucursal_id', 'integer'],
        ['venta', 'usuario_id', 'integer'],
    ];

    /** tabla.columna que en las bases en uso aceptan nulos. */
    private const ACEPTAN_NULOS = [
        'asistencia_alerta.created_at',
        'asistencia_alerta.estado',
        'asistencia_alerta.nivel',
        'asistencia_alerta.tipo_alerta',
        'asistencia_alerta.updated_at',
        'asistencia_dia.created_at',
        'asistencia_dia.updated_at',
        'asistencia_frente.created_at',
        'asistencia_frente.estado',
        'asistencia_frente.updated_at',
        'asistencia_frente_aprobacion.accion',
        'asistencia_frente_aprobacion.created_at',
        'asistencia_frente_aprobacion.updated_at',
        'asistencia_frente_detalle.created_at',
        'asistencia_frente_detalle.estado_asistencia',
        'asistencia_frente_detalle.estado_revision',
        'asistencia_frente_detalle.horas_dominicales',
        'asistencia_frente_detalle.horas_dominicales_festivas',
        'asistencia_frente_detalle.horas_extra_diurnas',
        'asistencia_frente_detalle.horas_extra_diurnas_dom_fest',
        'asistencia_frente_detalle.horas_extra_nocturnas',
        'asistencia_frente_detalle.horas_extra_nocturnas_dom_fest',
        'asistencia_frente_detalle.horas_festivas',
        'asistencia_frente_detalle.horas_ordinarias',
        'asistencia_frente_detalle.horas_ordinarias_diurnas',
        'asistencia_frente_detalle.horas_ordinarias_nocturnas',
        'asistencia_frente_detalle.horas_trabajadas',
        'asistencia_frente_detalle.requiere_revision',
        'asistencia_frente_detalle.updated_at',
        'asistencia_incidencia.fecha_registro',
        'asistencia_marcaje.created_at',
        'asistencia_novedad_nomina.fecha_generacion',
        'asistencia_soporte_pdf.created_at',
        'asistencia_soporte_pdf.estado',
        'asistencia_soporte_pdf.updated_at',
        'auditoria_nomina_asistencia.fecha_hora',
        'autorizacion_liquidacion_excepcional.fecha_autorizacion',
        'caja.activa',
        'calendario_laboral.aplica_recargo',
        'calendario_laboral.created_at',
        'calendario_laboral.es_descanso_empresa',
        'calendario_laboral.es_festivo_nacional',
        'calendario_laboral.es_festivo_regional',
        'calendario_laboral.origen',
        'calendario_laboral.tipo_dia',
        'calendario_laboral.updated_at',
        'categoria.activo',
        'categoria.created_at',
        'categoria.impuesto_defecto',
        'categoria.updated_at',
        'comision_liquidacion.tipo',
        'comision_venta.modalidad',
        'compra.created_at',
        'compra.descuento_total',
        'compra.estado',
        'compra.fecha',
        'compra.impuestos_total',
        'compra_detalle.impuesto_valor',
        'descuento_cliente.empresa_id',
        'descuento_cliente.tercero_id',
        'empleado_turno.created_at',
        'empresa.activa',
        'empresa.configuracion',
        'empresa.created_at',
        'empresa_suscripcion.created_at',
        'empresa_suscripcion.updated_at',
        'factura.descuento',
        'frente_turno.created_at',
        'frente_turno.updated_at',
        'historial_sesion.activo',
        'historial_sesion.fecha_inicio',
        'inventario.stock_actual',
        'inventario.stock_minimo',
        'inventario.updated_at',
        'jornada_laboral_config.aplica_excepcion_sectorial',
        'jornada_laboral_config.created_at',
        'jornada_laboral_config.hora_diurna_fin',
        'jornada_laboral_config.hora_diurna_inicio',
        'jornada_laboral_config.hora_nocturna_fin',
        'jornada_laboral_config.hora_nocturna_inicio',
        'jornada_laboral_config.horas_mensuales_base',
        'jornada_laboral_config.horas_semanales_legales',
        'jornada_laboral_config.max_horas_extra_dia',
        'jornada_laboral_config.max_horas_extra_semana',
        'jornada_laboral_config.recargo_dominical_festivo',
        'jornada_laboral_config.recargo_extra_diurna',
        'jornada_laboral_config.recargo_extra_nocturna',
        'jornada_laboral_config.recargo_nocturno',
        'jornada_laboral_config.updated_at',
        'liquidacion_prestacion.created_at',
        'liquidacion_prestacion.updated_at',
        'lista_precios.activa',
        'lote.activo',
        'marca.activo',
        'marca.created_at',
        'marca.updated_at',
        'merma.estado',
        'merma.fecha',
        'motivo_merma.afecta_contabilidad',
        'movimiento_inventario.created_at',
        'periodo_asistencia.fecha_creacion',
        'precio_cliente.empresa_id',
        'precio_cliente.producto_presentacion_id',
        'precio_cliente.tercero_id',
        'precio_volumen.empresa_id',
        'precio_volumen.producto_presentacion_id',
        'producto.activo',
        'producto.atributos',
        'producto.costo',
        'producto.created_at',
        'producto.impoconsumo',
        'producto.iva_incluido',
        'producto.iva_porcentaje',
        'producto.maneja_inventario',
        'producto.maneja_lotes',
        'producto.maneja_serial',
        'producto.precio',
        'producto.tipo_producto',
        'producto.updated_at',
        'producto_presentacion.activo',
        'producto_presentacion.costo',
        'producto_presentacion.precio',
        'proyecto.created_at',
        'proyecto.estado',
        'proyecto.requiere_control_asistencia',
        'proyecto.updated_at',
        'proyecto_frente.created_at',
        'proyecto_frente.estado',
        'proyecto_frente.updated_at',
        'proyecto_frente_trabajador.created_at',
        'proyecto_frente_trabajador.estado',
        'proyecto_frente_trabajador.updated_at',
        'regla_descuento.activo',
        'serial_producto.estado',
        'sucursal.activa',
        'sucursal.consecutivo_actual',
        'suscripcion_pago.created_at',
        'suscripcion_pago.updated_at',
        'tercero.activo',
        'tercero.created_at',
        'tercero.es_cliente',
        'tercero.es_empleado',
        'tercero.es_proveedor',
        'tercero.tipo_documento',
        'tercero.updated_at',
        'traslado.created_at',
        'traslado.estado',
        'traslado.fecha',
        'turno_caja.estado',
        'turno_caja.fecha_apertura',
        'turno_trabajo.created_at',
        'turno_trabajo.horas_ordinarias_programadas',
        'turno_trabajo.updated_at',
        'unidad_medida.activo',
        'unidad_medida.created_at',
        'unidad_medida.permite_decimales',
        'unidad_medida.updated_at',
        'usuario.activo',
        'usuario.created_at',
        'usuario_sucursal.activo',
        'usuario_sucursal.es_default',
        'venta.estado_venta',
        'venta.fecha_emision',
        'venta.tipo_documento',
        'venta_detalle.monto_descuento',
    ];

    /** tabla.columna que en las bases en uso son NOT NULL. */
    private const EXIGEN_VALOR = [
        'descuento_cliente.created_at',
        'precio_cliente.created_at',
        'precio_volumen.created_at',
    ];

    public function up(): void
    {
        DB::statement('ALTER TABLE abonos_pagar ADD COLUMN IF NOT EXISTS cuenta_bancaria_id BIGINT');
        DB::statement('ALTER TABLE venta ADD COLUMN IF NOT EXISTS factus_numero VARCHAR(255)');
        DB::statement('ALTER TABLE venta ADD COLUMN IF NOT EXISTS factus_url VARCHAR(255)');
        DB::statement('ALTER TABLE venta_detalle ADD COLUMN IF NOT EXISTS costo_linea NUMERIC(38,2)');

        // NOT NULL sin valor por defecto, igual que en las bases en uso: solo se
        // exige si ninguna fila quedó sin día.
        DB::statement('ALTER TABLE rutas ADD COLUMN IF NOT EXISTS dia_semana INTEGER');
        DB::statement(<<<'MIG_SQL'
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM rutas WHERE dia_semana IS NULL) THEN
        ALTER TABLE rutas ALTER COLUMN dia_semana SET NOT NULL;
    END IF;
END
$$
MIG_SQL);

        // venta.fecha_emision es la única fecha con zona horaria en las bases en
        // uso; el dv de empresa y tercero es varchar (Hibernate no acepta char donde espera varchar).
        DB::statement(<<<'MIG_SQL'
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns
               WHERE table_schema = 'public' AND table_name = 'venta'
                 AND column_name = 'fecha_emision' AND data_type = 'timestamp without time zone') THEN
        ALTER TABLE venta ALTER COLUMN fecha_emision TYPE TIMESTAMP WITH TIME ZONE;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.columns
               WHERE table_schema = 'public' AND table_name = 'empresa'
                 AND column_name = 'dv' AND data_type = 'character') THEN
        ALTER TABLE empresa ALTER COLUMN dv TYPE VARCHAR(255);
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.columns
               WHERE table_schema = 'public' AND table_name = 'tercero'
                 AND column_name = 'dv' AND data_type = 'character') THEN
        ALTER TABLE tercero ALTER COLUMN dv TYPE VARCHAR(255);
    END IF;
END
$$
MIG_SQL);

        foreach (self::TIPOS as [$tabla, $columna, $tipo]) {
            DB::statement(<<<MIG_SQL
DO \$\$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns
               WHERE table_schema = 'public' AND table_name = '{$tabla}'
                 AND column_name = '{$columna}' AND data_type <> '{$tipo}') THEN
        ALTER TABLE {$tabla} ALTER COLUMN {$columna} TYPE {$tipo};
    END IF;
END
\$\$
MIG_SQL);
        }
        // Nulos: una base nueva no puede ser más estricta que las bases en uso;
        // el nodo del modo híbrido recibe sus filas tal cual vienen de la nube.
        foreach (self::ACEPTAN_NULOS as $tc) {
            [$tabla, $columna] = explode('.', $tc);
            DB::statement(<<<MIG_SQL
DO \$\$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns
               WHERE table_schema = 'public' AND table_name = '{$tabla}'
                 AND column_name = '{$columna}' AND is_nullable = 'NO') THEN
        ALTER TABLE {$tabla} ALTER COLUMN {$columna} DROP NOT NULL;
    END IF;
END
\$\$
MIG_SQL);
        }

        foreach (self::EXIGEN_VALOR as $tc) {
            [$tabla, $columna] = explode('.', $tc);
            DB::statement(<<<MIG_SQL
DO \$\$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM {$tabla} WHERE {$columna} IS NULL) THEN
        ALTER TABLE {$tabla} ALTER COLUMN {$columna} SET NOT NULL;
    END IF;
END
\$\$
MIG_SQL);
        }
    }

    public function down(): void
    {
        // Sin reversa: el esquema alineado es el que espera el backend.    
    }
};
