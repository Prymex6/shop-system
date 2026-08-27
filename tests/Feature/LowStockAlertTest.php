<?php

namespace Tests\Feature;

use App\Console\Commands\CheckReorderPoints;
use App\Mail\Tenant\LowStockAlertMail;
use App\Models\Tenant\Product;
use App\Models\Tenant\User;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tests\TenantTestCase;

/**
 * inventory:check-reorder-points used to only Log::warning() when a product
 * dropped below its reorder point with no supplier configured — nobody was
 * ever actually notified. Now dispatches LowStockAlertMail + a database
 * notification to managers. checkCurrentTenant() is tested directly
 * (bypassing handle()'s landlord Tenant::all() loop, which needs the
 * landlord schema TenantTestCase doesn't migrate).
 */
class LowStockAlertTest extends TenantTestCase
{
    private function runnableCommand(): CheckReorderPoints
    {
        $command = app(CheckReorderPoints::class);
        $ref = new \ReflectionProperty($command, 'output');
        $ref->setAccessible(true);
        $ref->setValue($command, new SymfonyStyle(new ArrayInput([]), new NullOutput));

        return $command;
    }

    public function test_low_stock_without_supplier_sends_mail_and_notification(): void
    {
        Mail::fake();
        Notification::fake();

        $manager = User::create([
            'name' => 'Manager', 'email' => 'manager@test.com',
            'password' => bcrypt('secret'), 'role' => 'manager', 'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Niski stan', 'slug' => 'niski-stan-' . uniqid(),
            'sku' => 'LOW-' . uniqid(), 'price' => 50, 'is_active' => true,
            'track_stock' => true, 'stock_quantity' => 2, 'reorder_point' => 5,
        ]);

        $this->runnableCommand()->checkCurrentTenant('test-tenant');

        Mail::assertQueued(LowStockAlertMail::class, fn ($mail) => $mail->product->is($product));
        Notification::assertSentTo($manager, LowStockNotification::class);
    }

    public function test_product_above_reorder_point_is_not_alerted(): void
    {
        Mail::fake();

        Product::create([
            'name' => 'OK stan', 'slug' => 'ok-stan-' . uniqid(),
            'sku' => 'OK-' . uniqid(), 'price' => 50, 'is_active' => true,
            'track_stock' => true, 'stock_quantity' => 50, 'reorder_point' => 5,
        ]);

        $this->runnableCommand()->checkCurrentTenant('test-tenant');

        Mail::assertNothingQueued();
    }
}
