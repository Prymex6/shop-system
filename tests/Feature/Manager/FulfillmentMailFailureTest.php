<?php

namespace Tests\Feature\Manager;

use App\Mail\Tenant\OrderShippedMail;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Mail;
use Tests\TenantTestCase;

/**
 * FulfillmentController::update() sent OrderShippedMail synchronously with
 * no try/catch, AFTER the fulfillment_status/shipped_at were already
 * committed — an SMTP failure surfaced as a hard error to the manager
 * despite the status change having genuinely succeeded (the same "action
 * succeeded but looks failed" pattern RefundService::process() had).
 */
class FulfillmentMailFailureTest extends TenantTestCase
{
    private function manager(): User
    {
        return User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_marking_order_shipped_succeeds_even_if_mail_queueing_throws(): void
    {
        Mail::shouldReceive('to')->andThrow(new \Exception('SMTP down'));

        $order = $this->createTestOrder(['fulfillment_status' => 'unfulfilled']);

        $response = $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->patch(route('tenant.manager.orders.fulfillment.update', $order), [
                'fulfillment_status' => 'shipped',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('shipped', $order->fresh()->fulfillment_status);
        $this->assertNotNull($order->fresh()->shipped_at);
    }

    public function test_marking_order_shipped_queues_notification_mail(): void
    {
        Mail::fake();

        $order = $this->createTestOrder(['fulfillment_status' => 'unfulfilled']);

        $this->actingAs($this->manager(), 'tenant')
            ->withoutTenantMiddleware()
            ->patch(route('tenant.manager.orders.fulfillment.update', $order), [
                'fulfillment_status' => 'shipped',
            ]);

        Mail::assertQueued(OrderShippedMail::class);
    }
}
