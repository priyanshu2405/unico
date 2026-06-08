<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Webhook;
use App\Models\User;
use App\Models\WalletLedger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{

    public function handleWebhook(Request $request)
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $endpoint_secret = config('services.stripe.webhook_secret');

        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        // 🔥 MAIN EVENT
        if ($event->type === 'checkout.session.completed') {

            $session = $event->data->object;

            if ($session->payment_status === 'paid') {

                $user = User::find($session->metadata->user_id ?? null);

                if ($user) {
                    $amount = $session->amount_total / 100;

                    // Duplicate check
                    $exists = WalletLedger::where('transaction_id', $session->id)->exists();

                    if (!$exists) {
                        DB::transaction(function () use ($user, $amount, $session) {
                            $user->wallet_balance += $amount;
                            $user->save();

                            WalletLedger::create([
                                'transaction_id' => $session->id,
                                'user_id' => $user->id,
                                'type' => 'credit',
                                'amount' => $amount,
                                'source' => 'stripe',
                                'description' => 'Wallet Top-up via Stripe Webhook',
                                'created_at' => now(),
                            ]);
                        });
                    }
                }
            }
        }

        return response('Webhook handled', 200);
    }

    public function save(Request $request)
    {
        $request->validate([
            'webhook_name'   => 'required|string',
            'webhook_url'    => 'required|url',
            'webhook_secret' => 'required|string',
            'status'         => 'required'
        ]);

        Webhook::updateOrCreate(
            ['id' => 1], // single webhook rakhna ho to
            [
                'name'   => $request->webhook_name,
                'url'    => $request->webhook_url,
                'secret' => $request->webhook_secret,
                'status' => $request->status
            ]
        );

        return back()->with('success', 'Webhook saved successfully');
    }
}
