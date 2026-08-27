<?php

namespace Tests\Feature;

use App\Console\Commands\SyncCarrierShipmentStatus;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\ShippingMethod;
use App\Services\FulfillmentService;
use App\Services\Shipping\InPostGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TenantTestCase;

/**
 * Delivery to an InPost parcel locker, end to end.
 *
 * The carrier is never really called: every test here fakes ShipX, so what
 * is under test is this application's half of the exchange — which orders
 * are allowed through checkout, what gets written down, and what happens
 * when the carrier says no or says nothing at all.
 */
class ParcelLockerDeliveryTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setSettings([
            'inpost_api_token' => 'test-token',
            'inpost_organization_id' => '12345',
        ]);

        Cache::flush();
    }

    private function lockerMethod(): ShippingMethod
    {
        return $this->createTestShippingMethod([
            'name' => 'Paczkomat InPost',
            'carrier' => 'inpost',
        ]);
    }

    private function product(): Product
    {
        return Product::create([
            'name' => 'Produkt',
            'slug' => 'produkt-' . uniqid(),
            'sku' => 'P-' . uniqid(),
            'price' => 50,
            'is_active' => true,
            'is_published' => true,
            'track_stock' => false,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Jan Kowalski',
            'customer_email' => 'jan@example.com',
            'customer_phone' => '123456789',
            'payment_method' => 'cash_on_delivery',
            'terms_accepted' => true,
            'shipping_address' => ['country' => 'PL', 'city' => 'Warszawa'],
            'items' => [['product_id' => $this->product()->id, 'quantity' => 1]],
        ], $overrides);
    }

    // ─── Picking a locker ────────────────────────────────────────────────

    public function test_lockers_are_listed_for_a_carrier_delivered_method(): void
    {
        Http::fake(['api.inpost.pl/*' => Http::response([
            'items' => [[
                'name' => 'WAW01N',
                'location_description' => 'przy sklepie Żabka',
                'address_details' => [
                    'street' => 'Prosta',
                    'building_number' => '51',
                    'city' => 'Warszawa',
                    'post_code' => '00-838',
                ],
                'location' => ['latitude' => 52.23, 'longitude' => 20.98],
            ]],
        ])]);

        $response = $this->withoutTenantMiddleware()->getJson(
            route('tenant.checkout.pickup-points', [
                'shipping_method_id' => $this->lockerMethod()->id,
                'city' => 'Warszawa',
            ])
        );

        $response->assertOk()->assertJsonPath('points.0.code', 'WAW01N');
        $response->assertJsonPath('points.0.street', 'Prosta 51');
        $response->assertJsonPath('points.0.postCode', '00-838');
    }

    public function test_a_courier_method_has_no_lockers_to_offer(): void
    {
        Http::fake();

        $this->withoutTenantMiddleware()
            ->getJson(route('tenant.checkout.pickup-points', [
                'shipping_method_id' => $this->createTestShippingMethod()->id,
                'city' => 'Warszawa',
            ]))
            ->assertOk()
            ->assertJsonPath('points', []);

        Http::assertNothingSent();
    }

    public function test_the_same_city_is_only_asked_about_once(): void
    {
        Http::fake(['api.inpost.pl/*' => Http::response(['items' => []])]);

        $method = $this->lockerMethod();

        foreach (range(1, 3) as $ignored) {
            $this->withoutTenantMiddleware()
                ->getJson(route('tenant.checkout.pickup-points', [
                    'shipping_method_id' => $method->id,
                    'city' => 'Warszawa',
                ]))
                ->assertOk();
        }

        Http::assertSentCount(1);
    }

    // ─── Checkout ────────────────────────────────────────────────────────

    public function test_checkout_refuses_a_locker_method_with_no_locker_chosen(): void
    {
        $response = $this->withoutTenantMiddleware()->postJson(
            route('tenant.checkout.store'),
            $this->checkoutPayload(['shipping_method_id' => $this->lockerMethod()->id])
        );

        $response->assertStatus(422);
        $this->assertSame(0, Order::count());
    }

    public function test_checkout_records_the_chosen_locker_on_the_order(): void
    {
        $this->withoutTenantMiddleware()->postJson(
            route('tenant.checkout.store'),
            $this->checkoutPayload([
                'shipping_method_id' => $this->lockerMethod()->id,
                'pickup_point_code' => 'WAW01N',
                'pickup_point_data' => [
                    'street' => 'Prosta 51',
                    'city' => 'Warszawa',
                    'postCode' => '00-838',
                ],
            ])
        )->assertOk();

        $order = Order::firstOrFail();

        $this->assertSame('WAW01N', $order->pickup_point_code);
        $this->assertSame('Prosta 51', $order->pickup_point_data['street']);
    }

    public function test_a_locker_submitted_against_a_courier_method_is_discarded(): void
    {
        $this->withoutTenantMiddleware()->postJson(
            route('tenant.checkout.store'),
            $this->checkoutPayload([
                'shipping_method_id' => $this->createTestShippingMethod()->id,
                'pickup_point_code' => 'WAW01N',
            ])
        )->assertOk();

        $this->assertNull(Order::firstOrFail()->pickup_point_code);
    }

    // ─── Buying the label ────────────────────────────────────────────────

    public function test_buying_a_label_records_the_tracking_number_and_ships_the_order(): void
    {
        Http::fake(['api.inpost.pl/*' => Http::response([
            'id' => 'shp_1',
            'tracking_number' => '632112345678901234567890',
            'status' => 'created',
        ])]);

        $order = $this->createTestOrder(['pickup_point_code' => 'WAW01N']);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.orders.label.create', $order->id))
            ->assertRedirect();

        $order->refresh();

        $this->assertSame('632112345678901234567890', $order->tracking_number);
        $this->assertSame('inpost', $order->tracking_carrier);
        $this->assertSame('shipped', $order->fulfillment_status);
        $this->assertNotNull($order->shipped_at);
    }

    public function test_an_order_with_no_locker_cannot_have_a_label_bought(): void
    {
        Http::fake();

        $order = $this->createTestOrder();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.orders.label.create', $order->id))
            ->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertNull($order->refresh()->tracking_number);
    }

    public function test_a_second_label_is_refused_rather_than_bought_twice(): void
    {
        Http::fake(['api.inpost.pl/*' => Http::response([
            'id' => 'shp_1',
            'tracking_number' => '6321',
            'status' => 'created',
        ])]);

        $order = $this->createTestOrder([
            'pickup_point_code' => 'WAW01N',
            'tracking_number' => '6321',
        ]);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.orders.label.create', $order->id))
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }

    public function test_a_rejection_from_the_carrier_leaves_the_order_unshipped(): void
    {
        Http::fake(['api.inpost.pl/*' => Http::response(['error' => 'invalid_point'], 400)]);

        $order = $this->createTestOrder(['pickup_point_code' => 'NOPE99']);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.orders.label.create', $order->id))
            ->assertSessionHas('error');

        $order->refresh();

        $this->assertNull($order->tracking_number);
        $this->assertSame('unfulfilled', $order->fulfillment_status);
    }

    // ─── Following the parcel ────────────────────────────────────────────

    public function test_a_delivered_parcel_moves_the_order_to_delivered(): void
    {
        Http::fake(['api.inpost.pl/*' => Http::response(['status' => 'delivered'])]);

        $order = $this->createTestOrder([
            'tracking_carrier' => 'inpost',
            'tracking_number' => '6321',
            'fulfillment_status' => 'shipped',
            'shipped_at' => now()->subDay(),
        ]);

        $delivered = SyncCarrierShipmentStatusRunner::run();

        $this->assertSame(1, $delivered);

        $order->refresh();

        $this->assertSame('delivered', $order->fulfillment_status);
        $this->assertNotNull($order->delivered_at);
    }

    public function test_a_parcel_still_in_transit_is_left_alone(): void
    {
        Http::fake(['api.inpost.pl/*' => Http::response(['status' => 'out_for_delivery'])]);

        $order = $this->createTestOrder([
            'tracking_carrier' => 'inpost',
            'tracking_number' => '6321',
            'fulfillment_status' => 'shipped',
            'shipped_at' => now()->subDay(),
        ]);

        $this->assertSame(0, SyncCarrierShipmentStatusRunner::run());
        $this->assertNull($order->refresh()->delivered_at);
    }

    public function test_an_unreachable_carrier_does_not_invent_a_delivery(): void
    {
        Http::fake(['api.inpost.pl/*' => Http::response('', 503)]);

        $order = $this->createTestOrder([
            'tracking_carrier' => 'inpost',
            'tracking_number' => '6321',
            'fulfillment_status' => 'shipped',
            'shipped_at' => now()->subDay(),
        ]);

        $this->assertSame(0, SyncCarrierShipmentStatusRunner::run());

        $order->refresh();

        $this->assertNull($order->delivered_at);
        $this->assertSame('shipped', $order->fulfillment_status);
    }

    public function test_an_order_shipped_by_another_carrier_is_not_asked_about(): void
    {
        Http::fake();

        $this->createTestOrder([
            'tracking_carrier' => 'dpd',
            'tracking_number' => '6321',
            'fulfillment_status' => 'shipped',
            'shipped_at' => now()->subDay(),
        ]);

        $this->assertSame(0, SyncCarrierShipmentStatusRunner::run());

        Http::assertNothingSent();
    }
}

/**
 * The command itself loops over landlord tenants, which the tenant test case
 * deliberately does not migrate. Its per-tenant half is a static method for
 * exactly this reason; this just saves repeating the container lookups.
 */
final class SyncCarrierShipmentStatusRunner
{
    public static function run(int $days = 30): int
    {
        return SyncCarrierShipmentStatus::syncForCurrentTenant(
            app(InPostGateway::class),
            app(FulfillmentService::class),
            $days,
        );
    }
}
