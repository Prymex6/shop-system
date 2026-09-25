<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    private string $apiToken;

    private string $zoneId;

    private string $serverIp;

    private string $baseUrl = 'https://api.cloudflare.com/client/v4';

    public function __construct()
    {
        $this->apiToken = config('services.cloudflare.api_token', '');
        $this->zoneId = config('services.cloudflare.zone_id', '');
        $this->serverIp = config('services.cloudflare.server_ip', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiToken) && !empty($this->zoneId) && !empty($this->serverIp);
    }

    /**
     * Dodaje rekord A dla subdomeny tenanta.
     * Returns the DNS record ID, or null if the call failed.
     */
    public function addSubdomain(string $subdomain): ?string
    {
        if (!$this->isConfigured()) {
            Log::info("Cloudflare: skipped, nothing configured - subdomain: {$subdomain}");

            return null;
        }

        $name = $subdomain . '.' . config('app.tenant_base_domain');
        $ip = $this->serverIp;

        $response = Http::withToken($this->apiToken)
            ->post("{$this->baseUrl}/zones/{$this->zoneId}/dns_records", [
                'type' => 'A',
                'name' => $name,
                'content' => $ip,
                'ttl' => 1,      // 1 = auto
                'proxied' => true,   // przez Cloudflare (SSL + ochrona)
            ]);

        if ($response->successful() && $response->json('success')) {
            $recordId = $response->json('result.id');
            Log::info("Cloudflare: dodano rekord DNS {$name} → {$ip} (ID: {$recordId})");

            return $recordId;
        }

        Log::error('Cloudflare: could not add the DNS record', [
            'subdomain' => $name,
            'errors' => $response->json('errors'),
        ]);

        return null;
    }

    /**
     * Usuwa rekord DNS po ID (zapisanym przy tworzeniu tenanta).
     */
    public function removeSubdomain(string $dnsRecordId): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $response = Http::withToken($this->apiToken)
            ->delete("{$this->baseUrl}/zones/{$this->zoneId}/dns_records/{$dnsRecordId}");

        if ($response->successful() && $response->json('success')) {
            Log::info("Cloudflare: deleted DNS record ID: {$dnsRecordId}");

            return true;
        }

        Log::error('Cloudflare: could not delete the DNS record', [
            'dns_record_id' => $dnsRecordId,
            'errors' => $response->json('errors'),
        ]);

        return false;
    }

    /**
     * Szuka rekordu DNS po nazwie subdomeny i usuwa go.
     * Used as a fallback when no ID was stored.
     */
    public function removeSubdomainByName(string $subdomain): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $name = $subdomain . '.' . config('app.tenant_base_domain');

        $response = Http::withToken($this->apiToken)
            ->get("{$this->baseUrl}/zones/{$this->zoneId}/dns_records", [
                'type' => 'A',
                'name' => $name,
            ]);

        $records = $response->json('result', []);

        if (empty($records)) {
            Log::warning("Cloudflare: nie znaleziono rekordu DNS dla {$name}");

            return false;
        }

        return $this->removeSubdomain($records[0]['id']);
    }
}
