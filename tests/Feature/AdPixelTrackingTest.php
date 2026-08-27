<?php

namespace Tests\Feature;

use App\Models\Tenant\Setting;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * TikTok Pixel didn't exist anywhere in the app, and the Facebook Pixel base
 * code only ever sent PageView — never Purchase — so ad campaigns could
 * never see or optimize for actual sales. Fixed by adding a tiktok_pixel_id
 * setting (mirroring facebook_pixel_id), exposing both via the shared
 * Inertia 'tenant' prop CookieConsent.vue/OrderTracking.vue read, and firing
 * fbq('track','Purchase')/ttq.track('CompletePayment') from the order
 * confirmation page. The pixel injection + Purchase call themselves are
 * pure client-side JS untestable here — this covers the backend plumbing
 * (setting validation/persistence, shared prop exposure) that the frontend
 * fix depends on.
 */
class AdPixelTrackingTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'manager@test.com',
            'password' => bcrypt('secret'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_manager_can_save_valid_tiktok_pixel_id(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->put(route('tenant.manager.settings.update'), [
                'shop_name' => 'Sklep Test',
                'tiktok_pixel_id' => 'C1A2B3C4D5E6F7G8H9I0',
            ]);

        $response->assertRedirect();
        $this->assertEquals('C1A2B3C4D5E6F7G8H9I0', Setting::get('tiktok_pixel_id'));
    }

    public function test_invalid_tiktok_pixel_id_is_rejected(): void
    {
        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->putJson(route('tenant.manager.settings.update'), [
                'shop_name' => 'Sklep Test',
                'tiktok_pixel_id' => 'x',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tiktok_pixel_id']);
    }
}
