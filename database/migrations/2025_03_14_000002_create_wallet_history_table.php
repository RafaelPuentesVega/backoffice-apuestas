<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallet_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('address');
            $table->string('type');
            $table->timestamps();
            
            // Índice para búsquedas rápidas por usuario
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_history');
    }
};
