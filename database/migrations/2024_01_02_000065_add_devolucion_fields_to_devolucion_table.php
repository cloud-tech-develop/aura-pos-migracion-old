<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devolucion', function (Blueprint $table) {
            $table->string('metodo_devolucion', 30)->nullable()->after('estado');
                // EFECTIVO | TRANSFERENCIA | NOTA_CREDITO | SIN_DEVOLUCION
            $table->boolean('afecto_cartera')->default(false)->after('metodo_devolucion');
            $table->decimal('monto_cartera_afectado', 18, 2)->nullable()->after('afecto_cartera');
        });

        DB::statement("COMMENT ON COLUMN devolucion.metodo_devolucion IS 'EFECTIVO | TRANSFERENCIA | NOTA_CREDITO | SIN_DEVOLUCION'");
        DB::statement("COMMENT ON COLUMN devolucion.afecto_cartera IS 'TRUE si se redujo saldo en cuentas_cobrar'");
        DB::statement("COMMENT ON COLUMN devolucion.monto_cartera_afectado IS 'Monto efectivamente descontado de cuentas_cobrar'");
    }

    public function down(): void
    {
        Schema::table('devolucion', function (Blueprint $table) {
            $table->dropColumn(['metodo_devolucion', 'afecto_cartera', 'monto_cartera_afectado']);
        });
    }
};