<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_empleado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa');
            $table->string('nombre', 50);
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['empresa_id', 'activo'], 'idx_tipos_empleado_empresa');
        });

        if (Schema::hasTable('usuario') && !Schema::hasColumn('usuario', 'tipo_empleado_id')) {
            Schema::table('usuario', function (Blueprint $table) {
                $table->foreignId('tipo_empleado_id')->nullable()->constrained('tipos_empleado')->nullOnDelete();
                $table->index('tipo_empleado_id', 'idx_usuario_tipo');
            });
        }

        Schema::create('locales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa');
            $table->string('nombre', 150);
            $table->string('direccion', 255);
            $table->double('latitud')->nullable();
            $table->double('longitud')->nullable();
            $table->string('imagen_fachada', 500)->nullable();
            $table->text('horario_json')->nullable();
            $table->text('preferencia_dias_json')->nullable();
            $table->foreignId('vendedor_actual_id')->nullable()->constrained('usuario')->nullOnDelete();
            $table->foreignId('vendedor_anterior_id')->nullable()->constrained('usuario')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['empresa_id', 'activo'], 'idx_locales_empresa');
            $table->index('vendedor_actual_id', 'idx_locales_vendedor_actual');
            $table->index('vendedor_anterior_id', 'idx_locales_vendedor_anterior');
        });

        Schema::create('rutas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa');
            $table->foreignId('vendedor_id')->constrained('usuario');
            $table->string('nombre', 100);
            $table->string('descripcion', 500)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['empresa_id', 'activo'], 'idx_rutas_empresa');
            $table->index('vendedor_id', 'idx_rutas_vendedor');
        });

        Schema::create('rutas_locales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruta_id')->constrained('rutas')->cascadeOnDelete();
            $table->foreignId('local_id')->constrained('locales');
            $table->integer('orden');
            $table->timestamp('created_at')->nullable();

            $table->index('ruta_id', 'idx_rutas_locales_ruta');
        });

        Schema::create('visitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa');
            $table->foreignId('local_id')->constrained('locales');
            $table->foreignId('vendedor_id')->constrained('usuario');
            $table->foreignId('ruta_id')->nullable()->constrained('rutas')->nullOnDelete();
            $table->timestamp('fecha_programada');
            $table->string('hora_programada', 5)->nullable();
            $table->timestamp('fecha_real')->nullable();
            $table->double('latitud_llegada')->nullable();
            $table->double('longitud_llegada')->nullable();
            $table->string('estado', 20)->default('PROGRAMADA');
            $table->string('observaciones', 500)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('empresa_id', 'idx_visitas_empresa');
            $table->index('vendedor_id', 'idx_visitas_vendedor');
            $table->index('fecha_programada', 'idx_visitas_fecha');
            $table->index('local_id', 'idx_visitas_local');
            $table->index('estado', 'idx_visitas_estado');
        });

        $empresaExists = DB::table('empresa')->where('id', 1)->exists();
        if ($empresaExists) {
            DB::table('tipos_empleado')->insert([
                ['empresa_id' => 1, 'nombre' => 'VENDEDOR', 'descripcion' => 'Empleado encargado de vender productos y atender clientes', 'activo' => true, 'created_at' => now()],
                ['empresa_id' => 1, 'nombre' => 'CAJERO', 'descripcion' => 'Empleado encargado de manejar transacciones en caja', 'activo' => true, 'created_at' => now()],
                ['empresa_id' => 1, 'nombre' => 'GERENTE', 'descripcion' => 'Encargado de la gestión general de la empresa', 'activo' => true, 'created_at' => now()],
                ['empresa_id' => 1, 'nombre' => 'ADMINISTRADOR', 'descripcion' => 'Encargado de las tareas administrativas', 'activo' => true, 'created_at' => now()],
                ['empresa_id' => 1, 'nombre' => 'OFICIOS', 'descripcion' => 'Empleado de apoyo para diversas tareas', 'activo' => true, 'created_at' => now()],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas');
        Schema::dropIfExists('rutas_locales');
        Schema::dropIfExists('rutas');
        Schema::dropIfExists('locales');

        if (Schema::hasTable('usuario') && Schema::hasColumn('usuario', 'tipo_empleado_id')) {
            Schema::table('usuario', function (Blueprint $table) {
                $table->dropForeign(['tipo_empleado_id']);
                $table->dropColumn('tipo_empleado_id');
            });
        }

        Schema::dropIfExists('tipos_empleado');
    }
};
