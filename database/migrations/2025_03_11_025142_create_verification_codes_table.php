<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('verification_codes', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable();
            $table->string('email')->unique();
            $table->string('code', 6);
            $table->string('type')->comment('withdrawal, wallet');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_codes');
    }
};
