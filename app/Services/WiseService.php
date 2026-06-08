<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WiseService
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.wise.url');
        $this->apiKey = config('services.wise.key');
    }

    private function headers()
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    public function getProfiles()
    {
        return Http::withHeaders($this->headers())
            ->get($this->baseUrl . '/v1/profiles')
            ->json();
    }

    public function createQuote($profileId, $sourceCurrency, $targetCurrency, $amount)
    {
        return Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/v3/quotes', [
                'profile' => $profileId,
                'sourceCurrency' => $sourceCurrency,
                'targetCurrency' => $targetCurrency,
                'sourceAmount' => $amount,
            ])
            ->json();
    }

    public function createRecipient($profileId, $accountHolderName, $iban)
    {
        return Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/v1/accounts', [
                'profile' => $profileId,
                'accountHolderName' => $accountHolderName,
                'details' => [
                    'iban' => $iban,
                ],
            ])
            ->json();
    }

    public function createTransfer($quoteId, $recipientId)
    {
        return Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/v1/transfers', [
                'targetAccount' => $recipientId,
                'quoteUuid' => $quoteId,
                'customerTransactionId' => uniqid(),
            ])
            ->json();
    }
}
