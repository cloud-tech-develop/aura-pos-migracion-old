<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tercero')) {
            Schema::create('tercero', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->nullable()->constrained('empresa');
                $table->string('tipo_documento', 20)->default('CC');
                $table->string('numero_documento', 50);
                $table->char('dv', 1)->nullable();
                $table->string('razon_social', 255)->nullable();
                $table->string('nombres', 150)->nullable();
                $table->string('apellidos', 150)->nullable();
                $table->string('direccion', 255)->nullable();
                $table->string('telefono', 50)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('email_fe', 150)->nullable();
                $table->string('responsabilidad_fiscal', 50)->nullable();
                $table->boolean('es_cliente')->default(true);
                $table->boolean('es_proveedor')->default(false);
                $table->boolean('es_empleado')->default(false);
                $table->boolean('activo')->default(true);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->timestamp('deleted_at')->nullable();

                $table->unique(['empresa_id', 'numero_documento']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tercero');
    }
};
