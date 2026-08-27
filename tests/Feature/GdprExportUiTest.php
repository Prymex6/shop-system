<?php

namespace Tests\Feature;

use App\Models\Tenant\Customer;
use Tests\TenantTestCase;

/**
 * GdprController::export() (data portability — a distinct GDPR right from
 * erasure/anonymization) was fully built and correct, but had zero UI entry
 * point anywhere in the storefront — no button, no link. Added a "Pobierz
 * moje dane" link to Account.vue; this test covers the backend contract it
 * relies on, which had no test coverage at all before (round 17 finding).
 */
class GdprExportUiTest extends TenantTestCase
{
    public function test_customer_can_download_their_data_export(): void
    {
        $customer = Customer::create([
            'name' => 'Klient', 'email' => 'klient@test.com', 'password' => bcrypt('secret'),
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->get(route('tenant.gdpr.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertDownload("moje-dane-{$customer->id}.json");
    }

    public function test_guest_cannot_download_gdpr_export(): void
    {
        $response = $this->withoutTenantMiddleware()->get(route('tenant.gdpr.export'));

        $response->assertRedirect();
    }
}
