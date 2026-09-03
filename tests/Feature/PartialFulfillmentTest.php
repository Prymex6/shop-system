<?php

namespace Tests\Feature;

use App\Mail\Tenant\OrderShippedMail;
use App\Models\Tenant\Order;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Mail;
use Tests\TenantTestCase;

/**
 * Finding 2764: Order.fulfillment_status was a single field for the whole
 * order, so "shipped 2 of 3 items" couldn't be expressed — a manager had to
 * either mark the whole order shipped early (lying about what's missing) or
 * leave it at "processing" (hiding that most of it already went out).
 * FulfillmentService now derives Order.fulfillment_status from its items'
 * own statuses, and both fulfillment controllers accept an optional
 * item_ids selection to update only some of an order's lines.
 */
class PartialFulfillmentTest extends TenantTestCase
{
    private function staff(string $role = 'fulfillment'): User
    {
        return User::create([
            'name' => 'Staff', 'email' => 'staff@test.com',
            'password' => bcrypt('s'), 'role' => $role, 'is_active' => true,
        ]);
    }

    private function orderWithTwoPhysicalItems(): array
    {
        $order = $this->createTestOrder(['fulfillment_status' => 'unfulfilled', 'payment_status' => 'paid']);
        $shirt = $order->items()->create([
            'name' => 'Koszulka', 'quantity' => 1, 'price' => 40, 'total' => 40, 'product_type' => 'physical',
        ]);
        $mug = $order->items()->create([
            'name' => 'Kubek', 'quantity' => 1, 'price' => 20, 'total' => 20, 'product_type' => 'physical',
        ]);

        return [$order, $shirt, $mug];
    }

    public function test_shipping_one_of_two_items_keeps_order_processing_not_shipped(): void
    {
        Mail::fake();
        [$order, $shirt, $mug] = $this->orderWithTwoPhysicalItems();

        $this->actingAs($this->staff(), 'tenant')
            ->withoutTenantMiddleware()
            ->patchJson(route('tenant.staff.fulfillment.update-status', $order), [
                'fulfillment_status' => 'shipped',
                'item_ids' => [$shirt->id],
            ])->assertOk();

        $order->refresh();
        $this->assertEquals('shipped', $shirt->fresh()->fulfillment_status);
        $this->assertEquals('unfulfilled', $mug->fresh()->fulfillment_status);
        $this->assertEquals('processing', $order->fulfillment_status);
        $this->assertNull($order->shipped_at);
        Mail::assertNotQueued(OrderShippedMail::class);
    }

    public function test_shipping_remaining_item_completes_order_and_sends_mail_once(): void
    {
        Mail::fake();
        [$order, $shirt, $mug] = $this->orderWithTwoPhysicalItems();
        $staff = $this->staff();

        $this->actingAs($staff, 'tenant')->withoutTenantMiddleware()
            ->patchJson(route('tenant.staff.fulfillment.update-status', $order), [
                'fulfillment_status' => 'shipped', 'item_ids' => [$shirt->id],
            ])->assertOk();

        $this->actingAs($staff, 'tenant')->withoutTenantMiddleware()
            ->patchJson(route('tenant.staff.fulfillment.update-status', $order), [
                'fulfillment_status' => 'shipped', 'item_ids' => [$mug->id],
            ])->assertOk();

        $order->refresh();
        $this->assertEquals('shipped', $order->fulfillment_status);
        $this->assertNotNull($order->shipped_at);
        Mail::assertQueued(OrderShippedMail::class, 1);
    }

    public function test_updating_without_item_ids_updates_the_whole_order(): void
    {
        Mail::fake();
        [$order] = $this->orderWithTwoPhysicalItems();

        $this->actingAs($this->staff(), 'tenant')->withoutTenantMiddleware()
            ->patchJson(route('tenant.staff.fulfillment.update-status', $order), [
                'fulfillment_status' => 'shipped',
            ])->assertOk();

        $order->refresh()->load('items');
        $this->assertTrue($order->items->every(fn ($i) => $i->fulfillment_status === 'shipped'));
        $this->assertEquals('shipped', $order->fulfillment_status);
        Mail::assertQueued(OrderShippedMail::class, 1);
    }

    public function test_digital_items_are_excluded_from_the_order_aggregate(): void
    {
        Mail::fake();
        $order = $this->createTestOrder(['fulfillment_status' => 'unfulfilled', 'payment_status' => 'paid']);
        $physical = $order->items()->create([
            'name' => 'Koszulka', 'quantity' => 1, 'price' => 40, 'total' => 40, 'product_type' => 'physical',
        ]);
        $order->items()->create([
            'name' => 'E-book', 'quantity' => 1, 'price' => 10, 'total' => 10, 'product_type' => 'digital',
        ]);

        // Only the physical item is ever touched — the digital one is
        // delivered separately and never gets a manual fulfillment update.
        $this->actingAs($this->staff(), 'tenant')->withoutTenantMiddleware()
            ->patchJson(route('tenant.staff.fulfillment.update-status', $order), [
                'fulfillment_status' => 'shipped', 'item_ids' => [$physical->id],
            ])->assertOk();

        $order->refresh();
        $this->assertEquals('shipped', $order->fulfillment_status);
        Mail::assertQueued(OrderShippedMail::class, 1);
    }

    public function test_manager_fulfillment_endpoint_also_supports_partial_item_selection(): void
    {
        Mail::fake();
        [$order, $shirt, $mug] = $this->orderWithTwoPhysicalItems();
        $manager = User::create([
            'name' => 'Manager', 'email' => 'mgr@test.com',
            'password' => bcrypt('s'), 'role' => 'manager', 'is_active' => true,
        ]);

        $this->actingAs($manager, 'tenant')->withoutTenantMiddleware()
            ->patch(route('tenant.manager.orders.fulfillment.update', $order), [
                'fulfillment_status' => 'shipped',
                'item_ids' => [$shirt->id],
            ])->assertRedirect();

        $order->refresh();
        $this->assertEquals('shipped', $shirt->fresh()->fulfillment_status);
        $this->assertEquals('unfulfilled', $mug->fresh()->fulfillment_status);
        $this->assertEquals('processing', $order->fulfillment_status);
    }

    public function test_order_without_items_falls_back_to_direct_status_update(): void
    {
        Mail::fake();
        $order = $this->createTestOrder(['fulfillment_status' => 'unfulfilled', 'payment_status' => 'paid']);

        $this->actingAs($this->staff(), 'tenant')->withoutTenantMiddleware()
            ->patchJson(route('tenant.staff.fulfillment.update-status', $order), [
                'fulfillment_status' => 'shipped',
            ])->assertOk();

        $order->refresh();
        $this->assertEquals('shipped', $order->fulfillment_status);
        $this->assertNotNull($order->shipped_at);
        Mail::assertQueued(OrderShippedMail::class, 1);
    }
}
