<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // PLAN DE CUENTAS
        // ─────────────────────────────────────────────
        Schema::create('plan_cuenta', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('empresa_id');

            $table->string('codigo', 20);
            $table->string('codigo_dian', 20)->nullable(); // homologación DIAN futura

            $table->string('nombre', 200);

            $table->string('tipo', 20);        // ACTIVO | PASIVO | PATRIMONIO | INGRESO | GASTO | COSTO | ORDEN
            $table->string('naturaleza', 10);  // DEBITO | CREDITO

            $table->smallInteger('nivel')->default(1);

            $table->unsignedBigInteger('padre_id')->nullable();

            $table->boolean('activa')->default(true);
            $table->boolean('auxiliar')->default(false);

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->foreign('padre_id')->references('id')->on('plan_cuenta');

            $table->unique(['empresa_id', 'codigo']);
        });

        Schema::table('plan_cuenta', function (Blueprint $table) {
            $table->index('empresa_id');
            $table->index('padre_id');
        });

        // ─────────────────────────────────────────────
        // ASIENTO CONTABLE
        // ─────────────────────────────────────────────
        Schema::create('asiento_contable', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('empresa_id');

            $table->date('fecha');

            $table->string('numero_comprobante', 30)->nullable(); // consecutivo contable tipo CD-000001

            $table->string('descripcion', 500);

            $table->string('tipo_origen', 30)->default('MANUAL');
            $table->unsignedBigInteger('origen_id')->nullable();

            $table->decimal('total_debito', 18, 2)->default(0);
            $table->decimal('total_credito', 18, 2)->default(0);

            $table->string('estado', 20)->default('CONTABILIZADO');

            $table->unsignedBigInteger('usuario_id')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('empresa_id')->references('id')->on('empresa');
        });

        Schema::table('asiento_contable', function (Blueprint $table) {
            $table->index(['empresa_id', 'fecha']);
            $table->index(['tipo_origen', 'origen_id']);
        });

        // ─────────────────────────────────────────────
        // DETALLE ASIENTO
        // ─────────────────────────────────────────────
        Schema::create('asiento_detalle', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('asiento_id');
            $table->unsignedBigInteger('cuenta_id');

            $table->string('descripcion', 300)->nullable();

            $table->decimal('debito', 18, 2)->default(0);
            $table->decimal('credito', 18, 2)->default(0);

            $table->foreign('asiento_id')
                ->references('id')
                ->on('asiento_contable')
                ->onDelete('cascade');

            $table->foreign('cuenta_id')
                ->references('id')
                ->on('plan_cuenta');
        });

        Schema::table('asiento_detalle', function (Blueprint $table) {
            $table->index('asiento_id');
            $table->index('cuenta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asiento_detalle');
        Schema::dropIfExists('asiento_contable');
        Schema::dropIfExists('plan_cuenta');
    }
};