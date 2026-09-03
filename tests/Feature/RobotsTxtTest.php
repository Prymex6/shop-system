<?php

namespace Tests\Feature;

use Tests\TenantTestCase;

/**
 * robots.txt disallowed English placeholder paths ("/checkout", "/order/",
 * "/payment/") that never existed in this Polish-language storefront —
 * Googlebot was free to crawl the real /kasa, /koszyk, /moje-konto, and
 * most sensitively /zamowienie/{token}/sledzenie (an order-tracking URL
 * with an access token in the path).
 */
class RobotsTxtTest extends TenantTestCase
{
    public function test_robots_disallows_real_sensitive_paths(): void
    {
        $response = $this->withoutTenantMiddleware()->get(route('tenant.robots'));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('Disallow: /kasa', $content);
        $this->assertStringContainsString('Disallow: /koszyk', $content);
        $this->assertStringContainsString('Disallow: /moje-konto', $content);
        $this->assertStringContainsString('Disallow: /zamowienie/', $content);
        $this->assertStringContainsString('Disallow: /manager/', $content);
        $this->assertStringContainsString('Disallow: /staff/', $content);
    }

    public function test_robots_no_longer_lists_nonexistent_english_paths(): void
    {
        $response = $this->withoutTenantMiddleware()->get(route('tenant.robots'));
        $content = $response->getContent();

        $this->assertStringNotContainsString('Disallow: /checkout', $content);
        $this->assertStringNotContainsString('Disallow: /order/', $content);
    }
}
