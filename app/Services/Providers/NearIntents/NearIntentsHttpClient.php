<?php

namespace App\Services\Providers\NearIntents;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NearIntentsHttpClient
{
    private string $baseUrl;
    private ?string $apiKey;
    private int $timeoutSeconds;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.near_intents.base_url', 'https://1click.chaindefuser.com'), '/');
        $this->apiKey = config('services.near_intents.api_key');
        $this->timeoutSeconds = (int) config('services.near_intents.timeout_seconds', 20);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTokens(): array
    {
        $response = $this->client()
            ->retry(2, 250, throw: false)
            ->get('/v0/tokens');

        if ($response->failed()) {
            throw new RuntimeException('Failed to retrieve supported tokens from NEAR Intents (HTTP ' . $response->status() . ').');
        }

        $json = $response->json();
        if (!is_array($json)) {
            throw new RuntimeException('Unexpected token catalog format received from NEAR Intents.');
        }

        return $json;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function postQuote(array $payload): array
    {
        $response = $this->client()->post('/v0/quote', $payload);

        if ($response->failed()) {
            $errorBody = $response->json();
            $message = is_array($errorBody)
                ? ($errorBody['message'] ?? $errorBody['error'] ?? 'Unable to obtain quote for this route.')
                : 'Unable to obtain quote for this route.';

            throw new RuntimeException((string) $message, $response->status());
        }

        $json = $response->json();
        if (!is_array($json) || !isset($json['quote']) || !is_array($json['quote'])) {
            throw new RuntimeException('Invalid quote structure returned by execution provider.');
        }

        return $json;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function postDepositSubmit(array $payload): array
    {
        $response = $this->client()->post('/v0/deposit/submit', $payload);

        if ($response->failed()) {
            $errorBody = $response->json();
            $message = is_array($errorBody)
                ? ($errorBody['message'] ?? $errorBody['error'] ?? 'Provider rejected deposit transaction submission.')
                : 'Provider rejected deposit transaction submission.';

            throw new RuntimeException((string) $message, $response->status());
        }

        return is_array($response->json()) ? $response->json() : ['status' => 'submitted'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(string $depositAddress, ?string $depositMemo = null): array
    {
        $query = ['depositAddress' => $depositAddress];
        if ($depositMemo !== null && trim($depositMemo) !== '') {
            $query['depositMemo'] = trim($depositMemo);
        }

        $response = $this->client()
            ->retry(2, 200, throw: false)
            ->get('/v0/status', $query);

        if ($response->failed()) {
            throw new RuntimeException('Failed to query swap status from provider (HTTP ' . $response->status() . ').');
        }

        $json = $response->json();
        if (!is_array($json) || !isset($json['status'])) {
            throw new RuntimeException('Malformed status payload received from provider.');
        }

        return $json;
    }

    private function client(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeoutSeconds);

        if ($this->apiKey !== null && trim($this->apiKey) !== '') {
            $request = $request->withToken(trim($this->apiKey));
        }

        return $request;
    }
}

