<?php

namespace Tests\Feature;

use App\Models\Tenant\Product;
use Illuminate\Support\Facades\DB;
use Tests\TenantTestCase;

/**
 * CheckoutController::store() locked cart items via Product::lockForUpdate()
 * in whatever order the client's cart array happened to be in — two
 * customers checking out the same two products in opposite cart order
 * could lock them in opposite order and deadlock on MySQL (SQLSTATE[40001]),
 * surfacing as a failed order. Items are now sorted by product_id before
 * locking so every checkout acquires locks in the same global order.
 */
class CheckoutLockOrderTest extends TenantTestCase
{
    public function test_products_are_locked_in_ascending_id_order_regardless_of_cart_order(): void
    {
        $productA = Product::create(['name' => 'A', 'slug' => 'a', 'price' => 10, 'type' => 'physical', 'status' => 'active', 'is_published' => true, 'track_stock' => false]);
        $productB = Product::create(['name' => 'B', 'slug' => 'b', 'price' => 10, 'type' => 'physical', 'status' => 'active', 'is_published' => true, 'track_stock' => false]);

        // productB.id > productA.id (auto-increment) — submit in DESCENDING
        // (reverse) cart order to prove sorting actually happens, not just
        // coincidentally matches.
        $shippingMethod = $this->createTestShippingMethod();

        $lockedIds = [];
        $listener = function ($query) use (&$lockedIds) {
            // Only the lockForUpdate()->findOrFail() query — not the
            // 'exists:products,id' validation rule's separate count query,
            // which runs earlier against the original (unsorted) item order
            // and would otherwise make this test observe the wrong queries.
            if (str_starts_with($query->sql, 'select * from "products"') && !empty($query->bindings)) {
                $lockedIds[] = $query->bindings[0];
            }
        };

        DB::listen($listener);

        $this->withoutTenantMiddleware()->postJson(route('tenant.checkout.store'), [
            'customer_name' => 'Jan Kowalski',
            'customer_email' => 'jan@example.com',
            'customer_phone' => '123456789',
            'payment_method' => 'cash_on_delivery',
            'terms_accepted' => true,
            'subtotal' => 20.00,
            'shipping_method_id' => $shippingMethod->id,
            'shipping_address' => ['country' => 'PL'],
            'items' => [
                ['product_id' => $productB->id, 'quantity' => 1],
                ['product_id' => $productA->id, 'quantity' => 1],
            ],
        ]);

        $lockedIds = array_values(array_unique($lockedIds));

        $this->assertSame(
            [$productA->id, $productB->id],
            $lockedIds,
            'Products must be locked in ascending id order regardless of cart submission order'
        );
    }
}
