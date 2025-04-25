<?php

namespace App\Console\Commands;

use App\Models\Membresia;
use App\Models\ProfitDistribution;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateDistributeProfits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profits:calculate-distribute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcula y distribuye las ganancias de los usuarios según sus membresías';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando cálculo y distribución de ganancias...');
        
        // Obtener usuarios con membresía activa
        $users = User::whereNotNull('membership_id')->get();
        
        $this->info("Procesando {$users->count()} usuarios con membresías activas.");
        
        DB::beginTransaction();
        
        try {
            foreach ($users as $user) {
                $this->processUserProfits($user);
            }
            
            DB::commit();
            $this->info('Distribución de ganancias completada con éxito.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al procesar ganancias', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('Error al procesar ganancias: ' . $e->getMessage());
        }
    }
    
    /**
     * Procesa las ganancias para un usuario específico
     */
    private function processUserProfits(User $user)
    {
        // Obtener la membresía del usuario
        $membresia = Membresia::find($user->membership_id);
        
        if (!$membresia) {
            $this->warn("Usuario {$user->id} tiene un tipo de membresía inválido.");
            return;
        }
        
        // Calculamos la ganancia total basada en el porcentaje de rendimiento de la membresía
        $capitalAmount = $user->capital_balance;
        $totalProfit = ($capitalAmount * $membresia->porcentaje_rendimiento) / 100;
        
        if ($totalProfit <= 0) {
            return;
        }
        
        // Calculamos las partes de la ganancia
        $sponsorCommission = 0;
        $systemCommission = 0;
        $userProfit = $totalProfit;
        
        // Si el usuario tiene sponsor, calculamos su comisión
        if ($user->sponsor_id) {
            $sponsorCommission = ($totalProfit * $membresia->porcentaje_comision_sponsor) / 100;
            $userProfit -= $sponsorCommission;
            
            // Actualizar el balance del sponsor
            $sponsor = User::find($user->sponsor_id);
            if ($sponsor) {
                $sponsor->network_balance += $sponsorCommission;
                $sponsor->save();
                
                // Registrar la distribución de ganancia para el sponsor
                ProfitDistribution::create([
                    'user_id' => $sponsor->id,
                    'source_user_id' => $user->id,
                    'amount' => $sponsorCommission,
                    'type' => 'sponsor_commission',
                    'description' => "Comisión de membresía de {$user->name}"
                ]);
            }
        }
        
        // Registrar la ganancia del usuario
        $user->earnings_balance += $userProfit;
        $user->save();
        
        // Registrar la distribución de ganancia para el usuario
        ProfitDistribution::create([
            'user_id' => $user->id,
            'source_user_id' => $user->id,
            'amount' => $userProfit,
            'type' => 'direct_profit',
            'description' => "Ganancia directa por membresía"
        ]);
        
        $this->line("Procesado usuario {$user->id}: Ganancia total: \${$totalProfit}, Usuario: \${$userProfit}, Sponsor: \${$sponsorCommission}");
    }
} 