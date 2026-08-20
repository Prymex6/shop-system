<?php

namespace Tests\Feature;

use App\Events\OrderCreated;
use App\Mail\Tenant\DownloadLinkMail;
use App\Mail\Tenant\NewOrderNotificationMail;
use App\Mail\Tenant\OrderConfirmedMail;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Supplier;
use Illuminate\Support\Facades\Mail;
use Tests\TenantTestCase;

/**
 * `{{ $order->shipping_address }}` used to interpolate an array-cast column
 * directly — a hard TypeError ("htmlspecialchars(): Argument #1 must be of
 * type string, array given") crashing order-confirmed.blade.php and
 * new-order-notification.blade.php (and pdf/invoice.blade.php too) on
 * literally every order that had a shipping address at all, physical or
 * digital. Also covers the dropship email's 'postal_code' vs 'postcode'
 * key mismatch, and the digital-delivery email that was never sent at all.
 */
class OrderEmailTemplatesTest extends TenantTestCase
{
    private function orderWithShippingAddress(array $overrides = []): Order
    {
        return $this->createTestOrder(array_merge([
            'shipping_address' => [
                'street' => 'ul. Testowa 5',
                'city' => 'Warszawa',
                'postcode' => '00-001',
                'country' => 'Polska',
            ],
        ], $overrides));
    }

    public function test_order_confirmed_mail_renders_with_shipping_address(): void
    {
        $order = $this->orderWithShippingAddress();

        $html = (new OrderConfirmedMail($order))->render();

        $this->assertStringContainsString('ul. Testowa 5', $html);
        $this->assertStringContainsString('00-001 Warszawa', $html);
    }

    public function test_new_order_notification_mail_renders_with_shipping_address(): void
    {
        $order = $this->orderWithShippingAddress();

        $html = (new NewOrderNotificationMail($order))->render();

        $this->assertStringContainsString('ul. Testowa 5', $html);
    }

    public function test_order_confirmed_mail_renders_without_shipping_address(): void
    {
        // Digital-only orders may have a null shipping_address — must not crash either.
        $order = $this->createTestOrder(['shipping_address' => null]);

        $html = (new OrderConfirmedMail($order))->render();

        $this->assertStringNotContainsString('Adres wysyłki', $html);
    }

    public function test_invoice_pdf_view_renders_with_shipping_address(): void
    {
        $order = $this->orderWithShippingAddress();
        $order->load('items');

        $html = view('pdf.invoice', [
            'order' => $order,
            'settings' => Setting::getAllAsArray(),
            'vatGroups' => [],
        ])->render();

        $this->assertStringContainsString('ul. Testowa 5', $html);
    }

    public function test_dropship_email_uses_correct_postcode_key(): void
    {
        $order = $this->orderWithShippingAddress();

        $supplier = Supplier::create(['name' => 'Test Supplier', 'is_active' => true]);
        $purchaseOrder = PurchaseOrder::create(['supplier_id' => $supplier->id, 'status' => 'draft']);
        $product = Product::create(['name' => 'P', 'slug' => 'p-dropship', 'price' => 9.99, 'type' => 'physical', 'status' => 'active']);
        $purchaseOrder->items()->create(['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 5]);

        $html = view('emails.tenant.dropship-order', ['order' => $order, 'supplier' => $supplier, 'purchaseOrder' => $purchaseOrder])->render();

        $this->assertStringContainsString('00-001', $html);
        $this->assertStringNotContainsString('postal_code', $html);
    }

    public function test_paid_digital_order_queues_download_link_mail(): void
    {
        Mail::fake();

        $product = Product::create([
            'name' => 'E-book', 'slug' => 'e-book', 'price' => 19.99,
            'type' => 'digital', 'status' => 'active',
        ]);

        $order = $this->createTestOrder(['payment_status' => 'paid']);
        $order->items()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'product_type' => 'digital',
            'quantity' => 1,
            'price' => 19.99,
            'total' => 19.99,
        ]);

        event(new OrderCreated($order->fresh()));

        Mail::assertQueued(DownloadLinkMail::class, fn ($mail) => $mail->hasTo($order->customer_email));
        $this->assertTrue($order->downloadLinks()->exists());
    }
}
