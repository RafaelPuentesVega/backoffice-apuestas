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
        Schema::table('withdrawals', function (Blueprint $table) {
            // Verificar si la columna no existe antes de intentar crearla
            if (!Schema::hasColumn('withdrawals', 'balance_type')) {
                $table->string('balance_type')->default('earnings')->after('withdrawal_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            if (Schema::hasColumn('withdrawals', 'balance_type')) {
                $table->dropColumn('balance_type');
            }
        });
    }
}; 