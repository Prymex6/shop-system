<?php

namespace Tests\Feature;

use App\Models\Tenant\Product;
use Illuminate\Support\Facades\Http;
use Tests\TenantTestCase;

/**
 * Finding 1176: CurrencyService (real NBP-rate conversion) and the currency
 * switcher (CurrencyController::set()) both existed and worked, but no
 * price anywhere on the storefront was ever actually converted or shown in
 * anything but PLN — enabled_currencies in the manager panel had zero
 * visible effect. HandleInertiaRequests now shares a currency_rate prop
 * (used by the useCurrency Vue composable) computed from CurrencyService.
 */
class CurrencyDisplayTest extends TenantTestCase
{
    private function fakeNbpRate(string $currency, float $rate): void
    {
        Http::fake([
            "https://api.nbp.pl/api/exchangerates/rates/A/{$currency}/*" => Http::response([
                'rates' => [['mid' => $rate]],
            ], 200),
        ]);
    }

    public function test_currency_rate_is_one_for_pln(): void
    {
        $this->withoutTenantMiddleware()
            ->get(route('tenant.shop'))
            ->assertInertia(fn ($page) => $page->where('current_currency', 'PLN')
                ->where('currency_rate', 1)
            );
    }

    public function test_switching_currency_updates_session_and_shared_rate(): void
    {
        $this->setSetting('enabled_currencies', json_encode(['PLN', 'EUR']), 'json');
        $this->fakeNbpRate('EUR', 4.25);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.currency.set'), ['currency' => 'EUR'])
            ->assertRedirect();

        $this->withoutTenantMiddleware()
            ->get(route('tenant.shop'))
            ->assertInertia(fn ($page) => $page->where('current_currency', 'EUR')
                ->where('currency_rate', 1 / 4.25)
            );
    }

    public function test_currency_not_in_enabled_list_falls_back(): void
    {
        $this->setSetting('enabled_currencies', json_encode(['PLN']), 'json');

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.currency.set'), ['currency' => 'EUR']);

        // The endpoint itself only validates against CurrencyService::SUPPORTED,
        // not the shop's enabled list — SetCurrency middleware is what actually
        // enforces enabled_currencies on the next request.
        $response->assertRedirect();

        $this->withoutTenantMiddleware()
            ->get(route('tenant.shop'))
            ->assertInertia(fn ($page) => $page->where('current_currency', 'PLN'));
    }

    public function test_product_price_is_unconverted_plain_pln_value_from_backend(): void
    {
        // The backend always sends the raw PLN price — conversion happens
        // client-side in useCurrency, not server-side, so this just guards
        // that nothing here accidentally starts mutating stored prices.
        $product = Product::create([
            'name' => 'Koszulka', 'slug' => 'koszulka', 'price' => 99.99,
            'is_active' => true, 'is_published' => true,
        ]);

        $this->withoutTenantMiddleware()
            ->get(route('tenant.product.show', $product->slug))
            ->assertInertia(fn ($page) => $page->where('product.price', '99.99'));
    }
}
