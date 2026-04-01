<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comision_config', function (Blueprint $table) {
            $table->string('modalidad', 20)->default('SERVICIO')->after('tipo');
            $table->unsignedInteger('categoria_id')->nullable()->after('producto_id');
            $table->foreign('categoria_id')->references('id')->on('categoria');

            $table->index(['categoria_id', 'empresa_id', 'activo'], 'idx_comision_config_categoria');
            $table->index(['modalidad', 'empresa_id', 'activo'], 'idx_comision_config_modalidad');
        });

        // Hacer producto_id nullable
        DB::statement('ALTER TABLE comision_config ALTER COLUMN producto_id DROP NOT NULL');

        // Eliminar constraint original
        DB::statement('ALTER TABLE comision_config DROP CONSTRAINT IF EXISTS chk_porcentajes');

        // Solo SERVICIO exige que los porcentajes sumen 100
        DB::statement("ALTER TABLE comision_config ADD CONSTRAINT chk_porcentajes_servicio CHECK (modalidad != 'SERVICIO' OR (porcentaje_tecnico + porcentaje_negocio = 100))");

        // Debe haber objetivo: producto o categoría
        DB::statement('ALTER TABLE comision_config ADD CONSTRAINT chk_objetivo_comision CHECK (producto_id IS NOT NULL OR categoria_id IS NOT NULL)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE comision_config DROP CONSTRAINT IF EXISTS chk_objetivo_comision');
        DB::statement('ALTER TABLE comision_config DROP CONSTRAINT IF EXISTS chk_porcentajes_servicio');
        DB::statement('ALTER TABLE comision_config ADD CONSTRAINT chk_porcentajes CHECK (porcentaje_tecnico + porcentaje_negocio = 100)');
        DB::statement('ALTER TABLE comision_config ALTER COLUMN producto_id SET NOT NULL');

        Schema::table('comision_config', function (Blueprint $table) {
            $table->dropIndex('idx_comision_config_modalidad');
            $table->dropIndex('idx_comision_config_categoria');
            $table->dropForeign(['categoria_id']);
            $table->dropColumn(['modalidad', 'categoria_id']);
        });
    }
};