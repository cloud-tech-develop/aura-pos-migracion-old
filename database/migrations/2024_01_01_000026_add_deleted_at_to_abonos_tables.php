<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('abonos_pagar') && !Schema::hasColumn('abonos_pagar', 'deleted_at')) {
            Schema::table('abonos_pagar', function (Blueprint $table) {
                $table->timestamp('deleted_at')->nullable()->after('updated_at');
            });
        }

        if (Schema::hasTable('abonos_cobrar') && !Schema::hasColumn('abonos_cobrar', 'deleted_at')) {
            Schema::table('abonos_cobrar', function (Blueprint $table) {
                $table->timestamp('deleted_at')->nullable()->after('updated_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('abonos_pagar') && Schema::hasColumn('abonos_pagar', 'deleted_at')) {
            Schema::table('abonos_pagar', function (Blueprint $table) {
                $table->dropColumn('deleted_at');
            });
        }

        if (Schema::hasTable('abonos_cobrar') && Schema::hasColumn('abonos_cobrar', 'deleted_at')) {
            Schema::table('abonos_cobrar', function (Blueprint $table) {
                $table->dropColumn('deleted_at');
            });
        }
    }
};
