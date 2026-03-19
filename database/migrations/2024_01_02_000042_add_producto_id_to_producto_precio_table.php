<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto_precio', function (Blueprint $table) {
            $table->unsignedBigInteger('producto_id')->nullable()->after('lista_precio_id');
            $table->foreign('producto_id')->references('id')->on('producto');
        });

        // Hacer nullable producto_presentacion_id
        DB::statement('ALTER TABLE producto_precio ALTER COLUMN producto_presentacion_id DROP NOT NULL');

        // CHECK: al menos uno de los dos debe estar presente
        DB::statement('ALTER TABLE producto_precio ADD CONSTRAINT chk_precio_tiene_producto CHECK (producto_presentacion_id IS NOT NULL OR producto_id IS NOT NULL)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE producto_precio DROP CONSTRAINT IF EXISTS chk_precio_tiene_producto');

        Schema::table('producto_precio', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropColumn('producto_id');
        });

        DB::statement('ALTER TABLE producto_precio ALTER COLUMN producto_presentacion_id SET NOT NULL');
    }
};