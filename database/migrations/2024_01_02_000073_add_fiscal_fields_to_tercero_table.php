<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tercero', function (Blueprint $table) {
            $table->string('tipo_persona', 10)->default('NATURAL')->nullable()->after('responsabilidad_fiscal');
            $table->string('regimen', 30)->default('NO_RESPONSABLE_IVA')->nullable()->after('tipo_persona');
            $table->boolean('gran_contribuyente')->default(false)->after('regimen');
            $table->boolean('auto_retenedor')->default(false)->after('gran_contribuyente');
            $table->string('codigo_ciiu', 10)->nullable()->after('auto_retenedor');
            $table->string('actividad_economica', 200)->nullable()->after('codigo_ciiu');
            $table->string('pais', 60)->default('Colombia')->nullable()->after('actividad_economica');
            $table->string('codigo_pais', 5)->default('CO')->nullable()->after('pais');
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