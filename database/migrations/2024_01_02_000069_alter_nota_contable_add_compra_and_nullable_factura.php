<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nota_contable', function (Blueprint $table) {

            // crear compra_id solo si falta
            if (!Schema::hasColumn('nota_contable', 'compra_id')) {
                $table->unsignedBigInteger('compra_id')->nullable()->after('factura_id');
            }
        });

        // factura nullable
        DB::statement('ALTER TABLE nota_contable ALTER COLUMN factura_id DROP NOT NULL');

        // foreign key solo después de existir columna
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM information_schema.table_constraints
                    WHERE constraint_name = 'nota_contable_compra_fk'
                ) THEN
                    ALTER TABLE nota_contable
                    ADD CONSTRAINT nota_contable_compra_fk
                    FOREIGN KEY (compra_id)
                    REFERENCES compra(id)
                    ON DELETE SET NULL;
                END IF;
            END
            $$;
        ");

        // índice
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_nota_contable_compra
            ON nota_contable(compra_id)
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE nota_contable DROP CONSTRAINT IF EXISTS nota_contable_compra_fk');
        DB::statement('DROP INDEX IF EXISTS idx_nota_contable_compra');

        Schema::table('nota_contable', function (Blueprint $table) {
            if (Schema::hasColumn('nota_contable', 'compra_id')) {
                $table->dropColumn('compra_id');
            }
        });

        DB::statement('ALTER TABLE nota_contable ALTER COLUMN factura_id SET NOT NULL');
    }
};