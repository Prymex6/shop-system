<?php

namespace Tests\Feature;

use App\Models\Tenant\Customer;
use Illuminate\Support\Str;
use Tests\TenantTestCase;

/**
 * Order::internal_notes (explicitly labeled "Tylko dla managera..." in the
 * manager UI) was serialized wholesale into the Inertia JSON payload sent
 * to the customer's own browser on both the order-tracking page and "Moje
 * konto" — readable via view-source/devtools even though no Vue template
 * rendered it. Now hidden from array/JSON serialization by default.
 */
class OrderInternalNotesLeakTest extends TenantTestCase
{
    public function test_internal_notes_not_exposed_on_guest_order_tracking_page(): void
    {
        $order = $this->createTestOrder([
            'internal_notes' => 'Klient trudny, uważać na chargeback',
            'tracking_token' => Str::random(32),
        ]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.order.tracking', $order->order_number) . '?token=' . $order->tracking_token);

        $response->assertOk();
        $response->assertInertia(function ($page) {
            $order = $page->toArray()['props']['order'];
            $this->assertArrayNotHasKey('internal_notes', $order);
        });
    }

    public function test_internal_notes_not_exposed_on_customer_account_page(): void
    {
        $customer = Customer::create(['name' => 'K', 'email' => 'k@test.com', 'password' => bcrypt('secret')]);
        $this->createTestOrder([
            'customer_id' => $customer->id,
            'internal_notes' => 'Poufna notatka managera',
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->get(route('tenant.account'));

        $response->assertOk();
        $response->assertInertia(function ($page) {
            $orders = $page->toArray()['props']['orders']['data'];
            foreach ($orders as $order) {
                $this->assertArrayNotHasKey('internal_notes', $order);
            }
        });
    }

    public function test_internal_notes_still_persists_to_database(): void
    {
        $order = $this->createTestOrder(['internal_notes' => 'Nadal zapisywane w bazie']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id, 'internal_notes' => 'Nadal zapisywane w bazie',
        ]);
    }
}
