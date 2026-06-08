<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletLedger;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('riskLevel');

        if (auth()->user()->account_type === 'manager') {
            $query->whereNotIn('account_type', ['admin', 'manager']);
        } else {
            // For Admin and others, exclude admins as requested
            $query->where('account_type', '!=', 'admin');
        }

        $users = $query->get();
        
        // Use a separate query for stats to ensure it matches the visible users
        $stats = [
            'total_balance' => $query->sum('wallet_balance'),
            'total_credits' => WalletLedger::where('type', 'credit')->sum('amount'),
            'total_debits' => WalletLedger::where('type', 'debit')->sum('amount'),
        ];

        if ($request->ajax() || $request->has('tab')) {
            $tab = $request->get('tab', 'users');
            if ($tab === 'users') {
                return view('dashboard.wallet.partials.users-table', compact('users'));
            } elseif ($tab === 'ledger') {
                $ledger = WalletLedger::with('user')->latest()->limit(20)->get();
                return view('dashboard.wallet.partials.ledger-table', compact('ledger'));
            }
        }

        $ledger = WalletLedger::with('user')->latest()->limit(20)->get();
        return view('dashboard.wallet.index', compact('users', 'ledger', 'stats'));
    }

    public function credit(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string'
        ]);

        $user = User::findOrFail($request->user_id);
        $newBalance = $user->wallet_balance + $request->amount;
        $user->update(['wallet_balance' => $newBalance]);

        WalletLedger::create([
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => $request->amount,
            'description' => $request->note ?? 'Manual Credit',
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => 'Wallet credited successfully.']);
        }

        return back()->with('success', 'Wallet credited successfully.');
    }

    public function debit(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string'
        ]);

        $user = User::findOrFail($request->user_id);
        
        if ($user->wallet_balance < $request->amount) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Insufficient balance.'], 422);
            }
            return back()->with('error', 'Insufficient balance.');
        }

        $newBalance = $user->wallet_balance - $request->amount;
        $user->update(['wallet_balance' => $newBalance]);

        WalletLedger::create([
            'user_id' => $user->id,
            'type' => 'debit',
            'amount' => $request->amount,
            'description' => $request->note ?? 'Manual Debit',
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => 'Wallet debited successfully.']);
        }

        return back()->with('success', 'Wallet debited successfully.');
    }
}
