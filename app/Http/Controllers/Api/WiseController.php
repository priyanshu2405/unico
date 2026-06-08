<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WiseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WiseController extends Controller
{
    public function sendMoney(Request $request, WiseService $wise)
    {
        try {
            $profiles = $wise->getProfiles();

            // Handle errors or missing profiles gracefully
            if (empty($profiles) || !is_array($profiles) || !isset($profiles[0]['id'])) {
                return response()->json([
                    'error' => 'No profiles found on your Wise account or invalid token/configuration',
                    'api_response' => $profiles
                ], 400);
            }

            $profileId = $profiles[0]['id'];

            // Get parameters with defaults as requested
            $sourceCurrency = $request->input('source_currency', 'USD');
            $targetCurrency = $request->input('target_currency', 'INR');
            $amount = (float)$request->input('amount', 100);
            $recipientName = $request->input('recipient_name', 'John Doe');
            $iban = $request->input('iban', 'IN1234567890');

            // 1. Create a Quote
            $quote = $wise->createQuote($profileId, $sourceCurrency, $targetCurrency, $amount);
            if (empty($quote) || !isset($quote['id'])) {
                Log::error('Wise SendMoney: Quote creation failed', ['response' => $quote]);
                return response()->json(['error' => 'Wise quote creation failed', 'details' => $quote], 400);
            }

            // 2. Create a Recipient Account
            $recipient = $wise->createRecipient($profileId, $recipientName, $iban);
            if (empty($recipient) || !isset($recipient['id'])) {
                Log::error('Wise SendMoney: Recipient creation failed', ['response' => $recipient]);
                return response()->json(['error' => 'Wise recipient creation failed', 'details' => $recipient], 400);
            }

            // 3. Create the Transfer
            $transfer = $wise->createTransfer($quote['id'], $recipient['id']);
            if (empty($transfer) || !isset($transfer['id'])) {
                Log::error('Wise SendMoney: Transfer creation failed', ['response' => $transfer]);
                return response()->json(['error' => 'Wise transfer creation failed', 'details' => $transfer], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Transfer created successfully via Wise',
                'data' => $transfer
            ]);

        } catch (\Exception $e) {
            Log::error('Wise SendMoney Exception: ' . $e->getMessage());
            return response()->json([
                'error' => 'An error occurred while processing the Wise transfer workflow',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
