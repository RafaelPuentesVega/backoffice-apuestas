<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BalanceTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    /**
     * Muestra el listado de transacciones para el panel de administración
     */
    public function index(Request $request)
    {
        $query = BalanceTransaction::with('user')
            ->orderBy('created_at', 'desc');
            
        // Filtros
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('type') && !empty($request->type)) {
            $query->where('transaction_type', $request->type);
        }
        
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $transactions = $query->paginate(15)->withQueryString();
        
        // Obtener estadísticas
        $stats = [
            'total' => BalanceTransaction::count(),
            'depositos' => BalanceTransaction::where('transaction_type', 'deposit')->count(),
            'retiros' => BalanceTransaction::where('transaction_type', 'withdrawal')->count(),
            'comisiones' => BalanceTransaction::where('transaction_type', 'commission')->count(),
        ];
        
        return Inertia::render('admin/Transactions/Index', [
            'transactions' => $transactions,
            'stats' => $stats,
            'filters' => $request->only(['search', 'type', 'status', 'date_from', 'date_to']),
        ]);
    }
} 