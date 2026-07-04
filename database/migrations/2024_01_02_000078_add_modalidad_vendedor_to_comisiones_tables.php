<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // comision_venta: modalidad y vendedor_id
        Schema::table('comision_venta', function (Blueprint $table) {
            if (!Schema::hasColumn('comision_venta', 'modalidad')) {
                $table->string('modalidad', 20)->default('SERVICIO')->after('tecnico_id');
            }
            if (!Schema::hasColumn('comision_venta', 'vendedor_id')) {
                $table->unsignedBigInteger('vendedor_id')->nullable()->after('modalidad');
                $table->foreign('vendedor_id')->references('id')->on('empleados');
            }
        });

        // comision_liquidacion: tipo, vendedor_id y tecnico_id nullable
        Schema::table('comision_liquidacion', function (Blueprint $table) {
            if (!Schema::hasColumn('comision_liquidacion', 'tipo')) {
                $table->string('tipo', 20)->default('TECNICO')->after('estado');
            }
            if (!Schema::hasColumn('comision_liquidacion', 'vendedor_id')) {
                $table->unsignedBigInteger('vendedor_id')->nullable()->after('tipo');
                $table->foreign('vendedor_id')->references('id')->on('empleados');
            }
        });

        // tecnico_id pasa a ser nullable (idempotente en PostgreSQL)
        DB::statement('ALTER TABLE comision_liquidacion ALTER COLUMN tecnico_id DROP NOT NULL');

        // pedido_vendedor: venta_id
        Schema::table('pedido_vendedor', function (Blueprint $table) {
            if (!Schema::hasColumn('pedido_vendedor', 'venta_id')) {
                $table->unsignedBigInteger('venta_id')->nullable()->after('id');
                $table->foreign('venta_id')->references('id')->on('venta');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pedido_vendedor', function (Blueprint $table) {
            $table->dropForeign(['venta_id']);
            $table->dropColumn('venta_id');
        });

        DB::statement('ALTER TABLE comision_liquidacion ALTER COLUMN tecnico_id SET NOT NULL');

        Schema::table('comision_liquidacion', function (Blueprint $table) {
            $table->dropForeign(['vendedor_id']);
            $table->dropColumn(['tipo', 'vendedor_id']);
        });

        Schema::table('comision_venta', function (Blueprint $table) {
            $table->dropForeign(['vendedor_id']);
            $table->dropColumn(['modalidad', 'vendedor_id']);
        });
    }
};