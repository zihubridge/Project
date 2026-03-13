<?php

namespace App\Services\Swap;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChangeNowService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.changenow.base_url'), '/');
        $this->apiKey  = config('services.changenow.api_key');

        if (!$this->apiKey) {
            throw new \RuntimeException('ChangeNOW API key is missing in configuration.');
        }
    }

    /**
     * Create an exchange transaction on ChangeNOW
     */
    public function createExchange(
        string $fromCurrency,
        string $toCurrency,
        string $destinationAddress,
        string $extraId,
        string $fromNetwork,
        string $toNetwork,
        string $fromAmount,
        string $type = 'direct'
    ): array {
        // Prepare the payload based on the ChangeNOW v2 POST /v2/exchange requirements
        $payload = [
            'fromCurrency' => strtolower($fromCurrency),
            'toCurrency'   => strtolower($toCurrency),
            'fromNetwork'  => strtolower($fromNetwork),
            'toNetwork'    => strtolower($toNetwork),
            'fromAmount'   => (string) $fromAmount,
            'toAmount'     => "", // Empty for direct flow
            'address'      => $destinationAddress,
            'extraId'      => $extraId,
            'refundAddress' => "",
            'refundExtraId' => "",
            'userId'       => "",
            'payload'      => "",
            'contactEmail' => "",
            'source'       => "",
            'flow'         => 'standard',
            'type'         => $type,
            'rateId'       => ""
        ];

        try {
            // ChangeNOW v2 Create Transaction is a POST request
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-changenow-api-key' => $this->apiKey,
                ])
                ->post($this->baseUrl . '/v2/exchange', $payload);

            if ($response->failed()) {
                Log::error('ChangeNOW API Swap Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload
                ]);
                throw new \RuntimeException("ChangeNOW API Error: " . ($response->json()['message'] ?? $response->body()));
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('ChangeNOW Service Exception', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to create ChangeNOW exchange: ' . $e->getMessage());
        }
    }
}