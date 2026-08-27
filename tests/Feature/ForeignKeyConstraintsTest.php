<?php

namespace Tests\Feature;

use App\Models\Tenant\Customer;
use App\Models\Tenant\GiftCard;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBundle;
use App\Models\Tenant\ProductBundleItem;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\PushSubscription;
use App\Models\Tenant\StaffReport;
use App\Models\Tenant\User;
use Tests\TenantTestCase;

/**
 * Several *_id columns were indexed but had no real FK constraint, risking
 * orphaned rows once the parent was deleted. Added in
 * 2026_08_11_000002_add_missing_fk_constraints.php — verifies the delete
 * behavior actually fires (nullOnDelete vs cascadeOnDelete) rather than just
 * that the migration runs.
 */
class ForeignKeyConstraintsTest extends TenantTestCase
{
    public function test_deleting_variant_nulls_bundle_item_reference(): void
    {
        $product = Product::create([
            'name' => 'Zestaw', 'slug' => 'zestaw-' . uniqid(),
            'sku' => 'SET-' . uniqid(), 'price' => 100, 'is_active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'V-' . uniqid(),
            'attributes' => ['color' => 'red'], 'stock_quantity' => 5,
        ]);
        $bundle = ProductBundle::create(['name' => 'Bundle', 'slug' => 'bundle-' . uniqid(), 'price' => 90, 'is_active' => true]);
        $item = ProductBundleItem::create([
            'bundle_id' => $bundle->id, 'product_id' => $product->id,
            'variant_id' => $variant->id, 'quantity' => 1,
        ]);

        $variant->delete();

        $this->assertNull($item->fresh()->variant_id);
    }

    public function test_deleting_order_nulls_gift_card_purchase_reference(): void
    {
        $order = $this->createTestOrder();
        $giftCard = GiftCard::create([
            'code' => 'GC-' . uniqid(), 'initial_value' => 100, 'current_value' => 100,
            'purchased_by_order_id' => $order->id, 'is_active' => true,
        ]);

        $order->delete();

        $this->assertNull($giftCard->fresh()->purchased_by_order_id);
    }

    public function test_deleting_customer_nulls_referred_by_on_referred_customers(): void
    {
        $referrer = Customer::create(['name' => 'A', 'email' => 'a@test.com', 'password' => bcrypt('s')]);
        $referred = Customer::create([
            'name' => 'B', 'email' => 'b@test.com', 'password' => bcrypt('s'),
            'loyalty_referred_by' => $referrer->id,
        ]);

        $referrer->delete();

        $this->assertNull($referred->fresh()->loyalty_referred_by);
    }

    public function test_deleting_staff_user_cascades_to_their_reports_and_push_subscriptions(): void
    {
        $staff = User::create([
            'name' => 'Staff', 'email' => 'staff@test.com',
            'password' => bcrypt('s'), 'role' => 'fulfillment', 'is_active' => true,
        ]);
        $report = StaffReport::create([
            'staff_user_id' => $staff->id, 'role' => 'fulfillment', 'title' => 'Raport', 'message' => 'Treść raportu',
        ]);
        $subscription = PushSubscription::create([
            'staff_user_id' => $staff->id, 'endpoint' => 'https://push.example/' . uniqid(),
            'p256dh_key' => 'key', 'auth_key' => 'auth',
        ]);

        $staff->delete();

        $this->assertNull($report->fresh());
        $this->assertNull($subscription->fresh());
    }
}
