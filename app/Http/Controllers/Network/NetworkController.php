<?php

namespace App\Http\Controllers\Network;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class NetworkController extends Controller
{
    /**
     * Display the user's direct referrals (level 1).
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        
        // Get direct referrals (users with sponsor_id = current user's id)
        $directReferrals = User::where('sponsor_id', $user->code_referral)
            ->select('id', 'name', 'email', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return Inertia::render('network/Index', [
            'directReferrals' => $directReferrals
        ]);
    }
}
