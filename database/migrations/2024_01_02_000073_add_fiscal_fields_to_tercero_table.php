<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tercero', function (Blueprint $table) {
            if (!Schema::hasColumn('tercero', 'tipo_persona')) {
                $table->string('tipo_persona', 10)->default('NATURAL')->nullable()->after('responsabilidad_fiscal');
            }
            if (!Schema::hasColumn('tercero', 'regimen')) {
                $table->string('regimen', 30)->default('NO_RESPONSABLE_IVA')->nullable()->after('tipo_persona');
            }
            if (!Schema::hasColumn('tercero', 'gran_contribuyente')) {
                $table->boolean('gran_contribuyente')->default(false)->after('regimen');
            }
            if (!Schema::hasColumn('tercero', 'auto_retenedor')) {
                $table->boolean('auto_retenedor')->default(false)->after('gran_contribuyente');
            }
            if (!Schema::hasColumn('tercero', 'codigo_ciiu')) {
                $table->string('codigo_ciiu', 10)->nullable()->after('auto_retenedor');
            }
            if (!Schema::hasColumn('tercero', 'actividad_economica')) {
                $table->string('actividad_economica', 200)->nullable()->after('codigo_ciiu');
            }
            if (!Schema::hasColumn('tercero', 'pais')) {
                $table->string('pais', 60)->default('Colombia')->nullable()->after('actividad_economica');
            }
            if (!Schema::hasColumn('tercero', 'codigo_pais')) {
                $table->string('codigo_pais', 5)->default('CO')->nullable()->after('pais');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tercero', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_persona',
                'regimen',
                'gran_contribuyente',
                'auto_retenedor',
                'codigo_ciiu',
                'actividad_economica',
                'pais',
                'codigo_pais',
            ]);
        });
    }
};