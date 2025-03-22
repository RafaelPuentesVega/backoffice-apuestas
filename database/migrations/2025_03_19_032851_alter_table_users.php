<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('capital_balance', 15, 2)->default(0);
            $table->decimal('earnings_balance', 15, 2)->default(0);
            $table->decimal('network_balance', 15, 2)->default(0);
            $table->unsignedTinyInteger('membership_id')->default(0);
            $table->unsignedBigInteger('referrer_id')->nullable();
            $table->foreign('referrer_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referrer_id']);
            $table->dropColumn(['capital_balance', 'earnings_balance', 'network_balance', 'membership_id', 'referrer_id']);
        });
    }
};
