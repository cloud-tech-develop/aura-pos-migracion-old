<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * V74: Nómina — origen del pago (medio y cuenta bancaria). Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nomina', function (Blueprint $table) {
            if (!Schema::hasColumn('nomina', 'medio_pago'))         $table->string('medio_pago', 20)->nullable(); // EFECTIVO | TRANSFERENCIA
            if (!Schema::hasColumn('nomina', 'cuenta_bancaria_id')) $table->unsignedBigInteger('cuenta_bancaria_id')->nullable();
            if (!Schema::hasColumn('nomina', 'fecha_pago'))         $table->timestamp('fecha_pago')->nullable();
        });

        // FK idempotente (solo si aún no existe).
        DB::statement('DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'fk_nomina_cuenta_bancaria\') THEN ALTER TABLE nomina ADD CONSTRAINT fk_nomina_cuenta_bancaria FOREIGN KEY (cuenta_bancaria_id) REFERENCES cuenta_bancaria(id); END IF; END $$;');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE nomina DROP CONSTRAINT IF EXISTS fk_nomina_cuenta_bancaria');
        Schema::table('nomina', function (Blueprint $table) {
            $table->dropColumn(['medio_pago', 'cuenta_bancaria_id', 'fecha_pago']);
        });
    }
};
