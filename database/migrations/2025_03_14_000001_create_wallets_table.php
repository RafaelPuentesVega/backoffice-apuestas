<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('address');
            $table->string('type');
            $table->timestamps();
            
            // Cada usuario solo puede tener una billetera
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
