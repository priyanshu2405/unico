<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WiseWebhookController extends Controller
{
    /**
     * Handle incoming webhooks from Wise
     */
    public function handle(Request $request)
    {
        Log::info('Wise Webhook Received:', [
            'headers' => collect($request->headers->all())->map(fn($v) => $v[0] ?? $v)->toArray(),
            'body' => $request->all()
        ]);

        // 1. Optional: Wise Payload Signature Verification
        // Wise signs webhook payloads with RS256 using public keys available at:
        // Sandbox: https://api.sandbox.transferwise.tech/v1/webhook-public-keys
        // Production: https://api.transferwise.com/v1/webhook-public-keys
        $signature = $request->header('X-Signature-SHA256');
        if ($signature) {
            Log::info('Wise Webhook: Signature verification placeholder. Signature found: ' . $signature);
        }

        // 2. Process webhook event data
        $eventType = $request->input('event_type');
        $data = $request->input('data', []);

        if (empty($eventType)) {
            // Support alternate Wise webhook payload formats
            $eventType = $request->input('event');
        }

        switch ($eventType) {
            case 'transfers#state-change':
            case 'transfer.state.change':
                $transferId = $data['transferId'] ?? $data['transfer_id'] ?? null;
                $state = $data['currentState'] ?? $data['current_state'] ?? null;
                Log::info("Wise Webhook: Transfer #{$transferId} state changed to {$state}");
                
                // TODO: Implement database updates or trigger internal application events
                // e.g., Update payout ledger status or notify user.
                break;

            case 'transfer.active':
                Log::info("Wise Webhook: Transfer marked as active.");
                break;

            default:
                Log::info("Wise Webhook: Received unhandled event '{$eventType}'");
                break;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook received and processed'
        ], 200);
    }
}
