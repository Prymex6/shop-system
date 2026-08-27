<?php

namespace Tests\Unit;

use App\Services\CloudflareService;
use Illuminate\Support\Facades\Http;
use Tests\TenantTestCase;

/**
 * Two bugs found while wiring CLOUDFLARE_SERVER_IP into config/services.php:
 *   - server_ip was never bound to any env() key at all — addSubdomain()
 *     always sent content: null, which Cloudflare's API rejects, even for
 *     an operator who fully configured api_token/zone_id
 *   - config('app.tenant_base_domain'), read by addSubdomain() to build the
 *     DNS record name, was never bound anywhere either — always null,
 *     producing a name like "{subdomain}." with no domain at all
 */
class CloudflareServiceTest extends TenantTestCase
{
    public function test_is_not_configured_without_server_ip(): void
    {
        config([
            'services.cloudflare.api_token' => 'token',
            'services.cloudflare.zone_id' => 'zone',
            'services.cloudflare.server_ip' => '',
        ]);

        $this->assertFalse((new CloudflareService)->isConfigured());
    }

    public function test_is_configured_with_all_three_values(): void
    {
        config([
            'services.cloudflare.api_token' => 'token',
            'services.cloudflare.zone_id' => 'zone',
            'services.cloudflare.server_ip' => '1.2.3.4',
        ]);

        $this->assertTrue((new CloudflareService)->isConfigured());
    }

    public function test_add_subdomain_sends_real_ip_and_full_domain_name(): void
    {
        config([
            'services.cloudflare.api_token' => 'token',
            'services.cloudflare.zone_id' => 'zone123',
            'services.cloudflare.server_ip' => '203.0.113.5',
            'app.tenant_base_domain' => 'example.com',
        ]);

        Http::fake([
            'api.cloudflare.com/*' => Http::response(['success' => true, 'result' => ['id' => 'rec1']], 200),
        ]);

        (new CloudflareService)->addSubdomain('mysklep');

        Http::assertSent(function ($request) {
            return $request['content'] === '203.0.113.5'
                && $request['name'] === 'mysklep.example.com';
        });
    }
}
