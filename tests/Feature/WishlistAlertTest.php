<?php

namespace Tests\Feature;

use App\Console\Commands\ProcessWishlistAlerts;
use App\Jobs\SendWishlistAlert;
use App\Mail\Tenant\WishlistAlertMail;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Product;
use App\Models\Tenant\Wishlist;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TenantTestCase;

/**
 * Round 23: wishlist was a pure "save for later" list with zero
 * re-engagement — no price-drop alert, no back-in-stock alert, unlike every
 * other retention mechanism in the app (abandoned cart, loyalty, marketing
 * campaigns). WishlistController::toggle() now snapshots price_at_added and
 * was_out_of_stock when an item is added; ProcessWishlistAlerts::processItem()
 * compares that snapshot against current product state on each scheduled run.
 *
 * The tenant-iteration wrapper around processItem() (ProcessWishlistAlerts::
 * handle()) needs App\Models\Landlord\Tenant, which isn't available under
 * TenantTestCase's tenant-only SQLite schema — see WishlistAlertCommandTest
 * (LandlordTestCase) for the smoke test that actually exercises that part.
 */
class WishlistAlertTest extends TenantTestCase
{
    private function customer(bool $optOut = false): Customer
    {
        return Customer::create([
            'name' => 'Klient', 'email' => 'klient-' . uniqid() . '@test.com',
            'password' => bcrypt('s'), 'marketing_opt_out' => $optOut,
        ]);
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Produkt', 'slug' => 'produkt-' . uniqid(),
            'sku' => 'W-' . uniqid(), 'price' => 100.0, 'is_active' => true,
            'is_published' => true, 'track_stock' => false,
        ], $overrides));
    }

    // ─── WishlistController::toggle() snapshot ──────────────────────────────

    public function test_adding_to_wishlist_snapshots_price_and_stock_state(): void
    {
        $customer = $this->customer();
        $product = $this->product(['price' => 49.99, 'track_stock' => true, 'stock_quantity' => 0]);

        $this->actingAs($customer, 'customer')
            ->withoutTenantMiddleware()
            ->post(route('tenant.wishlist.toggle', $product));

        $this->assertDatabaseHas('wishlists', [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'price_at_added' => 49.99,
            'was_out_of_stock' => true,
        ]);
    }

    // ─── ProcessWishlistAlerts::processItem() decision logic ────────────────

    public function test_dispatches_price_drop_alert_when_price_falls(): void
    {
        Bus::fake();
        $customer = $this->customer();
        $product = $this->product(['price' => 80.0]);
        $item = Wishlist::create(['customer_id' => $customer->id, 'product_id' => $product->id, 'price_at_added' => 100.0]);

        ProcessWishlistAlerts::processItem($item->fresh(['product']));

        Bus::assertDispatched(SendWishlistAlert::class, fn ($job) => $job->item->id === $item->id && $job->reason === 'price_drop');
    }

    public function test_does_not_dispatch_price_drop_when_price_unchanged_or_higher(): void
    {
        Bus::fake();
        $customer = $this->customer();
        $product = $this->product(['price' => 100.0]);
        $item = Wishlist::create(['customer_id' => $customer->id, 'product_id' => $product->id, 'price_at_added' => 100.0]);

        ProcessWishlistAlerts::processItem($item->fresh(['product']));

        Bus::assertNotDispatched(SendWishlistAlert::class);
    }

    public function test_dispatches_restock_alert_when_previously_out_of_stock_item_is_back(): void
    {
        Bus::fake();
        $customer = $this->customer();
        $product = $this->product(['track_stock' => true, 'stock_quantity' => 5]);
        $item = Wishlist::create([
            'customer_id' => $customer->id, 'product_id' => $product->id,
            'price_at_added' => $product->price, 'was_out_of_stock' => true,
        ]);

        ProcessWishlistAlerts::processItem($item->fresh(['product']));

        Bus::assertDispatched(SendWishlistAlert::class, fn ($job) => $job->item->id === $item->id && $job->reason === 'restock');
    }

    public function test_does_not_dispatch_restock_alert_for_item_that_was_never_out_of_stock(): void
    {
        Bus::fake();
        $customer = $this->customer();
        $product = $this->product(['track_stock' => true, 'stock_quantity' => 5]);
        $item = Wishlist::create([
            'customer_id' => $customer->id, 'product_id' => $product->id,
            'price_at_added' => $product->price, 'was_out_of_stock' => false,
        ]);

        ProcessWishlistAlerts::processItem($item->fresh(['product']));

        Bus::assertNotDispatched(SendWishlistAlert::class, fn ($job) => $job->reason === 'restock');
    }

    public function test_does_not_redispatch_already_notified_alerts(): void
    {
        Bus::fake();
        $customer = $this->customer();
        $product = $this->product(['price' => 50.0]);
        $item = Wishlist::create([
            'customer_id' => $customer->id, 'product_id' => $product->id,
            'price_at_added' => 100.0, 'price_drop_notified' => true,
        ]);

        ProcessWishlistAlerts::processItem($item->fresh(['product']));

        Bus::assertNotDispatched(SendWishlistAlert::class);
    }

    public function test_marks_item_out_of_stock_when_it_newly_goes_out_of_stock(): void
    {
        $customer = $this->customer();
        $product = $this->product(['track_stock' => true, 'stock_quantity' => 0]);
        $item = Wishlist::create([
            'customer_id' => $customer->id, 'product_id' => $product->id,
            'price_at_added' => $product->price, 'was_out_of_stock' => false,
        ]);

        ProcessWishlistAlerts::processItem($item->fresh(['product']));

        $this->assertTrue($item->fresh()->was_out_of_stock);
    }

    // ─── SendWishlistAlert job ───────────────────────────────────────────────

    public function test_job_sends_mail_and_marks_notified(): void
    {
        Mail::fake();
        $customer = $this->customer();
        $product = $this->product(['price' => 50.0]);
        $item = Wishlist::create(['customer_id' => $customer->id, 'product_id' => $product->id, 'price_at_added' => 100.0]);

        (new SendWishlistAlert($item, 'price_drop'))->handle();

        Mail::assertSent(WishlistAlertMail::class);
        $this->assertTrue($item->fresh()->price_drop_notified);
    }

    public function test_job_respects_marketing_opt_out(): void
    {
        Mail::fake();
        $customer = $this->customer(optOut: true);
        $product = $this->product(['price' => 50.0]);
        $item = Wishlist::create(['customer_id' => $customer->id, 'product_id' => $product->id, 'price_at_added' => 100.0]);

        (new SendWishlistAlert($item, 'price_drop'))->handle();

        Mail::assertNothingSent();
        $this->assertFalse($item->fresh()->price_drop_notified);
    }
}
