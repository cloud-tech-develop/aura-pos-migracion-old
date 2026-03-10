<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonos_cobrar', function (Blueprint $table) {
            if (!Schema::hasColumn('abonos_cobrar', 'turno_caja_id')) {
                $table->foreignId('turno_caja_id')
                      ->nullable()
                      ->constrained('turno_caja')
                      ->onDelete('set null');
                $table->index('turno_caja_id', 'idx_abono_cobrar_turno');
            }
        });

        Schema::table('abonos_pagar', function (Blueprint $table) {
            if (!Schema::hasColumn('abonos_pagar', 'turno_caja_id')) {
                $table->foreignId('turno_caja_id')
                      ->nullable()
                      ->constrained('turno_caja')
                      ->onDelete('set null');
                $table->index('turno_caja_id', 'idx_abono_pagar_turno');
            }
        });
    }

    public function down(): void
    {
        Schema::table('abonos_cobrar', function (Blueprint $table) {
            $table->dropForeign(['turno_caja_id']);
            $table->dropIndex('idx_abono_cobrar_turno');
            $table->dropColumn('turno_caja_id');
        });

        Schema::table('abonos_pagar', function (Blueprint $table) {
            $table->dropForeign(['turno_caja_id']);
            $table->dropIndex('idx_abono_pagar_turno');
            $table->dropColumn('turno_caja_id');
        });
    }
};
