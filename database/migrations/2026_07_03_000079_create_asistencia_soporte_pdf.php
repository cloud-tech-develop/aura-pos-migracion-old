<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V79: Soporte PDF de asistencia (Fase C). El líder sube el PDF firmado/escaneado
 * a Cloudflare R2 (archivo_url) con su hash para detectar duplicados. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asistencia_soporte_pdf')) {
            return;
        }

        Schema::create('asistencia_soporte_pdf', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedBigInteger('asistencia_frente_id')->nullable();
            $table->unsignedBigInteger('plantilla_id')->nullable();
            $table->unsignedBigInteger('proyecto_id')->nullable();
            $table->unsignedBigInteger('frente_id')->nullable();
            $table->unsignedBigInteger('lider_id')->nullable();
            $table->date('fecha');
            $table->string('archivo_url', 500);
            $table->string('nombre_archivo', 255)->nullable();
            $table->unsignedBigInteger('peso_archivo')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->string('hash_archivo', 80)->nullable();
            $table->string('estado', 20)->default('CARGADO');
            $table->string('observacion', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->timestamp('deleted_at')->nullable();

            $table->foreign('empresa_id')->references('id')->on('empresa');
            $table->foreign('asistencia_frente_id')->references('id')->on('asistencia_frente')->onDelete('cascade');
            $table->index(['frente_id', 'fecha'], 'idx_soporte_pdf_frente');
            $table->index('asistencia_frente_id', 'idx_soporte_pdf_asis');
            $table->index(['empresa_id', 'hash_archivo'], 'idx_soporte_pdf_hash');
        });

        DB::statement("ALTER TABLE asistencia_soporte_pdf ADD CONSTRAINT chk_soporte_pdf_estado CHECK (estado IN ('CARGADO', 'EN_REVISION', 'APROBADO', 'RECHAZADO', 'ANULADO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia_soporte_pdf');
    }
};
