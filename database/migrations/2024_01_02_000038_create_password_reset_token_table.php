<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_token', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('usuario_id')->constrained('usuario')->onDelete('cascade');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->boolean('usado')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index('token', 'idx_prt_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_token');
    }
};