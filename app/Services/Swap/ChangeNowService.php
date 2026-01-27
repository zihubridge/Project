<?php

namespace App\Services\Swap;

use Illuminate\Support\Facades\Http;

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
     * Estimate swap amount using ChangeNOW
     */
    public function estimateAmount(
        string $fromCurrency,
        string $toCurrency,
        ?string $fromNetwork = null,
        ?string $toNetwork = null,
        ?string $fromAmount = null,
        ?string $toAmount = null,
        string $type = 'direct'
    ): array {
        $type = strtolower($type);

        if (!in_array($type, ['direct', 'reverse'], true)) {
            throw new \InvalidArgumentException("Invalid type. Use 'direct' or 'reverse'.");
        }

        if ($type === 'direct' && (!$fromAmount || (float)$fromAmount <= 0)) {
            throw new \InvalidArgumentException("fromAmount is required for direct type.");
        }

        if ($type === 'reverse' && (!$toAmount || (float)$toAmount <= 0)) {
            throw new \InvalidArgumentException("toAmount is required for reverse type.");
        }

        $params = [
            'fromCurrency' => strtolower($fromCurrency),
            'toCurrency'   => strtolower($toCurrency),
            'fromNetwork'  => $fromNetwork ? strtolower($fromNetwork) : null,
            'toNetwork'    => $toNetwork ? strtolower($toNetwork) : null,
            'flow'         => 'standard', // ChangeNOW v2 usually requires flow
        ];

        if ($type === 'direct') {
            $params['fromAmount'] = (string) $fromAmount;
        } else {
            $params['toAmount'] = (string) $toAmount;
        }

        // Filter out null values
        $params = array_filter($params);

        try {
            $res = Http::timeout(20)
                ->acceptJson()
                ->withHeaders([
                    'x-changenow-api-key' => $this->apiKey,
                ])
                ->get($this->baseUrl . '/v2/exchange/estimated-amount', $params);

            if ($res->failed()) {
                throw new \RuntimeException("ChangeNOW API Error: " . $res->body());
            }

            return $res->json();
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'ChangeNOW estimate failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
