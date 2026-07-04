<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V63: Comprobante contable manual sobre asiento_contable.
 * Idempotente: solo agrega lo que falte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asiento_contable', function (Blueprint $table) {
            if (!Schema::hasColumn('asiento_contable', 'tipo_comprobante'))        $table->string('tipo_comprobante', 20)->nullable();
            if (!Schema::hasColumn('asiento_contable', 'beneficiario_tercero_id')) $table->unsignedBigInteger('beneficiario_tercero_id')->nullable();
            if (!Schema::hasColumn('asiento_contable', 'beneficiario_nombre'))     $table->string('beneficiario_nombre', 200)->nullable();
            if (!Schema::hasColumn('asiento_contable', 'beneficiario_direccion'))  $table->string('beneficiario_direccion', 200)->nullable();
            if (!Schema::hasColumn('asiento_contable', 'beneficiario_telefono'))   $table->string('beneficiario_telefono', 50)->nullable();
            if (!Schema::hasColumn('asiento_contable', 'ciudad'))                  $table->string('ciudad', 100)->nullable();
            if (!Schema::hasColumn('asiento_contable', 'fecha_vencimiento'))       $table->date('fecha_vencimiento')->nullable();
        });

        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS ux_asiento_empresa_comprobante ON asiento_contable (empresa_id, numero_comprobante) WHERE numero_comprobante IS NOT NULL");
        DB::statement("CREATE INDEX IF NOT EXISTS ix_asiento_tipo_comprobante ON asiento_contable (empresa_id, tipo_comprobante)");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS ix_asiento_tipo_comprobante");
        DB::statement("DROP INDEX IF EXISTS ux_asiento_empresa_comprobante");
        Schema::table('asiento_contable', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_comprobante', 'beneficiario_tercero_id', 'beneficiario_nombre',
                'beneficiario_direccion', 'beneficiario_telefono', 'ciudad', 'fecha_vencimiento',
            ]);
        });
    }
};
