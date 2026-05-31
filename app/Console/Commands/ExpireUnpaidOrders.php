<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Models\Tenant\Order;
use App\Services\OrderCancellationService;
use Illuminate\Console\Command;

/**
 * Stock is decremented the moment checkout is submitted (CheckoutController
 * ::store()), for every payment method — including online gateways, where
 * the customer is redirected off-site and may simply never come back
 * (abandoned payment session, closed tab, expired gateway session). Nothing
 * ever released that stock. Only targets payment_status='awaiting_payment'
 * (online-gateway orders) — plain 'pending' (cash-on-delivery/bank-transfer)
 * orders are legitimately long-lived awaiting fulfillment and must never be
 * auto-cancelled here.
 */
class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid {--hours=2 : Age threshold in hours}';

    protected $description = 'Cancel abandoned online-payment orders past the age threshold, releasing their reserved stock';

    public function handle(OrderCancellationService $cancellationService): int
    {
        $hours = (int) $this->option('hours');
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $count = self::expireForCurrentTenant($cancellationService, $hours);

                if ($count > 0) {
                    $this->line("[{$tenant->id}] Expired {$count} abandoned unpaid order(s).");
                }
            } catch (\Throwable $e) {
                $this->error("[{$tenant->id}] Error: " . $e->getMessage());
            }

            tenancy()->end();
        }

        return self::SUCCESS;
    }

    /**
     * Runs the actual expiry logic against whatever tenant is currently
     * initialized. Split out from handle() so it's directly testable
     * without needing the landlord tenants table (TenantTestCase only
     * migrates the tenant schema, not the landlord one).
     */
    public static function expireForCurrentTenant(OrderCancellationService $cancellationService, int $hours): int
    {
        $expired = Order::where('payment_status', 'awaiting_payment')
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subHours($hours))
            ->get();

        $count = 0;
        foreach ($expired as $order) {
            $result = $cancellationService->cancel($order);
            if ($result['success']) {
                $count++;
            }
        }

        return $count;
    }
}
