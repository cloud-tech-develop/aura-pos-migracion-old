<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centros_costos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id')->nullable();
            $table->string('codigo', 20);
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('centro_costo_padre_id')->nullable();
            $table->string('tipo', 50)->nullable();
                // OPERATIVO | ADMINISTRATIVO | VENTAS | PRODUCCION | FINANCIERO
            $table->integer('nivel')->nullable();
            $table->boolean('permite_movimientos')->default(true);
            $table->decimal('presupuesto_asignado', 15, 2)->nullable();
            $table->unsignedBigInteger('responsable_id')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('usuario_creacion')->nullable();
            $table->unsignedBigInteger('usuario_modificacion')->nullable();

            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->foreign('sucursal_id')->references('id')->on('sucursal');
            $table->foreign('centro_costo_padre_id')->references('id')->on('centros_costos');

            $table->unique(['empresa_id', 'codigo'], 'uq_cc_empresa_codigo');
        });

        DB::statement("ALTER TABLE centros_costos ADD CONSTRAINT chk_centros_costos_tipo CHECK (tipo IN ('OPERATIVO','ADMINISTRATIVO','VENTAS','PRODUCCION','FINANCIERO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('centros_costos');
    }
};