<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Thin client for the Beds24 V1 JSON API (channel manager: Booking.com, Expedia,
 * Airbnb). Auth is the account API key (Settings → Account → Account Access);
 * property-level writes also need the property's propKey.
 */
class Beds24Client
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) config('services.beds24.api_key');
        $this->baseUrl = rtrim((string) config('services.beds24.base_url'), '/');
    }

    public function configured(): bool
    {
        return $this->apiKey !== '';
    }

    public function propId(): string
    {
        return (string) config('services.beds24.prop_id');
    }

    /**
     * Call a Beds24 JSON function. Merges the apiKey auth into the payload.
     * Beds24 returns HTTP 200 even on logical errors (body carries {"error": ...}).
     */
    public function request(string $function, array $payload = []): array
    {
        $auth = ['apiKey' => $this->apiKey];
        if (isset($payload['authentication']) && is_array($payload['authentication'])) {
            $auth = array_merge($auth, $payload['authentication']);
        }
        $payload['authentication'] = $auth;

        $json = Http::timeout(30)
            ->acceptJson()
            ->post("{$this->baseUrl}/{$function}", $payload)
            ->json();

        return is_array($json) ? $json : [];
    }

    /** All properties (with room types + Beds24 roomIds) on the account. */
    public function getProperties(): array
    {
        return $this->request('getProperties')['getProperties'] ?? [];
    }
}
