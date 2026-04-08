<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodo_contable', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->smallInteger('anio');
            $table->smallInteger('mes');
            $table->string('estado', 10)->default('ABIERTO'); // ABIERTO | CERRADO
            $table->date('fecha_apertura')->default(now());
            $table->date('fecha_cierre')->nullable();
            $table->unsignedBigInteger('usuario_apertura_id')->nullable();
            $table->unsignedBigInteger('usuario_cierre_id')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('empresa_id')->references('id')->on('empresa');

            $table->unique(['empresa_id', 'anio', 'mes'], 'uq_periodo_empresa_anio_mes');
        });

        DB::statement("ALTER TABLE periodo_contable ADD CONSTRAINT chk_periodo_estado CHECK (estado IN ('ABIERTO', 'CERRADO'))");

        Schema::table('asiento_contable', function (Blueprint $table) {
            $table->unsignedBigInteger('periodo_contable_id')->nullable()->after('id');
            $table->foreign('periodo_contable_id')->references('id')->on('periodo_contable');
        });
    }

    public function down(): void
    {
        Schema::table('asiento_contable', function (Blueprint $table) {
            $table->dropForeign(['periodo_contable_id']);
            $table->dropColumn('periodo_contable_id');
        });

        Schema::dropIfExists('periodo_contable');
    }
};