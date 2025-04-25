<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parameters', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('description')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });
        
        // Insertar parámetros iniciales para configurar días de retiro
        DB::table('parameters')->insert([
            [
                'key' => 'withdrawal_days_capital',
                'value' => json_encode([4]), // Jueves (0 = domingo, 1 = lunes, ... 6 = sábado)
                'description' => 'Días permitidos para retiro de capital',
                'group' => 'withdrawals',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'withdrawal_days_earnings',
                'value' => json_encode([0]), // Domingo
                'description' => 'Días permitidos para retiro de ganancias',
                'group' => 'withdrawals',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'withdrawal_days_network',
                'value' => json_encode([0,1,2,3,4,5,6]), // Todos los días
                'description' => 'Días permitidos para retiro de ganancias de red',
                'group' => 'withdrawals',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'withdrawal_min_amount',
                'value' => '50',
                'description' => 'Monto mínimo para solicitudes de retiro',
                'group' => 'withdrawals',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'withdrawal_max_amount',
                'value' => '5000',
                'description' => 'Monto máximo para solicitudes de retiro',
                'group' => 'withdrawals',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Parámetros para depósitos
            [
                'key' => 'deposit_min_amount',
                'value' => '50',
                'description' => 'Monto mínimo para solicitudes de depósito',
                'group' => 'deposits',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'deposit_max_amount',
                'value' => '10000',
                'description' => 'Monto máximo para solicitudes de depósito',
                'group' => 'deposits',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parameters');
    }
}; 