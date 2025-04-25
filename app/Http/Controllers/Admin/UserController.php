<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Models\BalanceTransaction;
use App\Models\MembershipHistory;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::with('membership')->latest()->paginate(10);
        
        return inertia('admin/Users/Index', [
            'users' => $users
        ]);
    }

    /**
     * Display the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::with([
            'membership',
            'sponsor',
            'referidos' => function($query) {
                $query->with('membership');
            }
        ])->findOrFail($id);
        
        // Cargar historial de depósitos
        $deposits = Deposit::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Cargar historial de retiros
        $withdrawals = Withdrawal::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Cargar historial de transacciones
        $transactions = BalanceTransaction::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Cargar historial de membresías
        $membershipHistory = MembershipHistory::with('membresia')
            ->where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return inertia('admin/Users/Show', [
            'user' => $user,
            'deposits' => $deposits,
            'withdrawals' => $withdrawals,
            'transactions' => $transactions,
            'membershipHistory' => $membershipHistory
        ]);
    }
}
