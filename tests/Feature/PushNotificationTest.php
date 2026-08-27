<?php

namespace Tests\Feature;

use App\Models\Tenant\PushSubscription;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Tests for staff push notification subscriptions.
 */
class PushNotificationTest extends TenantTestCase
{
    private function chef(): User
    {
        return User::create([
            'name' => 'Chef', 'email' => 'chef@test.com',
            'password' => bcrypt('s'), 'role' => 'chef', 'is_active' => true,
        ]);
    }

    public function test_staff_can_subscribe_to_push_notifications(): void
    {
        $chef = $this->chef();

        $response = $this->actingAs($chef, 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.staff.push.subscribe'), [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
                'p256dh_key' => base64_encode('fake-p256dh-key-value-here-32chars'),
                'auth_key' => base64_encode('fake-auth-key-16'),
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('push_subscriptions', [
            'staff_user_id' => $chef->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
        ]);
    }

    public function test_staff_can_unsubscribe_from_push_notifications(): void
    {
        $chef = $this->chef();

        PushSubscription::create([
            'staff_user_id' => $chef->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/endpoint-to-remove',
            'p256dh_key' => 'key',
            'auth_key' => 'auth',
        ]);

        $response = $this->actingAs($chef, 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.staff.push.unsubscribe'), [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/endpoint-to-remove',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('push_subscriptions', [
            'staff_user_id' => $chef->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/endpoint-to-remove',
        ]);
    }

    public function test_subscribe_requires_endpoint(): void
    {
        $chef = $this->chef();

        $response = $this->actingAs($chef, 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.staff.push.subscribe'), [
                'p256dh_key' => 'key',
                'auth_key' => 'auth',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['endpoint']);
    }

    public function test_duplicate_subscription_updates_existing(): void
    {
        $chef = $this->chef();

        $this->actingAs($chef, 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.staff.push.subscribe'), [
                'endpoint' => 'https://fcm.googleapis.com/same-endpoint',
                'p256dh_key' => 'key1',
                'auth_key' => 'auth1',
            ]);

        $this->actingAs($chef, 'tenant')
            ->withoutTenantMiddleware()
            ->postJson(route('tenant.staff.push.subscribe'), [
                'endpoint' => 'https://fcm.googleapis.com/same-endpoint',
                'p256dh_key' => 'key2',
                'auth_key' => 'auth2',
            ]);

        $this->assertEquals(1, PushSubscription::where('endpoint', 'https://fcm.googleapis.com/same-endpoint')->count());
    }
}
