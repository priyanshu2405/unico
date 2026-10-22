<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\PromotionalCode;
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MembershipController extends Controller
{
    /**
     * Show membership benefits and status dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $activeMembership = $user->activeMembership;
        $membershipHistory = Membership::where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        return view('dashboard.membership.index', compact('user', 'activeMembership', 'membershipHistory'));
    }

    /**
     * Activate or renew a membership
     */
    public function activate(Request $request)
    {
        $request->validate([
            'plan_type' => 'required|in:monthly,yearly',
        ]);

        $user = Auth::user();
        $result = MembershipService::activateMembership($user, $request->plan_type, 'wallet');

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Verify promotional code via AJAX for checkout
     */
    public function verifyPromoCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $code = strtoupper(trim($request->code));
        $subtotal = (float) $request->subtotal;

        // Check if matching active membership promo code
        if ($user->hasActiveMembership() && $user->membership_promo_code === $code) {
            return response()->json([
                'valid' => true,
                'discount' => round($subtotal * 0.01, 2),
                'message' => 'Membership 1% Discount confirmed on your account.',
            ]);
        }

        $promo = PromotionalCode::where('code', $code)->first();
        if (!$promo) {
            return response()->json([
                'valid' => false,
                'discount' => 0,
                'message' => 'Invalid promotional code.',
            ], 422);
        }

        $validation = $promo->isValidFor($user, $subtotal);
        if (!$validation['valid']) {
            return response()->json([
                'valid' => false,
                'discount' => 0,
                'message' => $validation['message'],
            ], 422);
        }

        $discount = $promo->calculateDiscount($subtotal);

        return response()->json([
            'valid' => true,
            'discount' => $discount,
            'message' => "Promo Code Applied: {$promo->title}",
        ]);
    }
}
