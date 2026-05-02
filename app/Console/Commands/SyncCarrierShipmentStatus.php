<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Models\Tenant\Order;
use App\Services\FulfillmentService;
use App\Services\Shipping\InPostGateway;
use Illuminate\Console\Command;

/**
 * Ask the carrier where its parcels actually are.
 *
 * Until this existed, a shipment's status was whatever a manager last typed
 * into the order by hand: an order marked "shipped" stayed "shipped" forever,
 * the customer's tracking page never moved, and "delivered" only ever appeared
 * if somebody remembered to click it.
 *
 * Only InPost is asked, because it is the only carrier this shop integrates
 * with; an order shipped by anything else keeps its manually entered status.
 */
class SyncCarrierShipmentStatus extends Command
{
    protected $signature = 'shipments:sync-status {--days=30 : How far back to keep asking about a parcel}';

    protected $description = 'Update delivery status of in-transit shipments from the carrier';

    public function handle(InPostGateway $inPost, FulfillmentService $fulfillment): int
    {
        $days = max(1, (int) $this->option('days'));

        foreach (Tenant::all() as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $delivered = self::syncForCurrentTenant($inPost, $fulfillment, $days);

                if ($delivered > 0) {
                    $this->line("[{$tenant->id}] {$delivered} shipment(s) marked delivered.");
                }
            } catch (\Throwable $error) {
                $this->error("[{$tenant->id}] Error: " . $error->getMessage());
            }

            tenancy()->end();
        }

        return self::SUCCESS;
    }

    /**
     * Runs against whichever tenant is currently initialized.
     *
     * Split out from handle() for the same reason ExpireUnpaidOrders does it:
     * the tenant test case migrates the tenant schema only, so a test can call
     * this without a landlord tenants table existing at all.
     *
     * @return int how many orders were newly marked delivered
     */
    public static function syncForCurrentTenant(
        InPostGateway $inPost,
        FulfillmentService $fulfillment,
        int $days,
    ): int {
        if (!$inPost->isConfigured()) {
            return 0;
        }

        $inTransit = Order::query()
            ->where('tracking_carrier', 'inpost')
            ->whereNotNull('tracking_number')
            ->whereNull('delivered_at')
            ->where('shipped_at', '>=', now()->subDays($days))
            ->get();

        $delivered = 0;

        foreach ($inTransit as $order) {
            $status = $inPost->trackingStatus($order->tracking_number);

            // Null means the carrier could not be reached. Leaving the order
            // as it is means the next run asks again; writing something down
            // would turn an outage into a delivery that never happened.
            if ($status === null) {
                continue;
            }

            if ($status === 'delivered') {
                $fulfillment->updateStatus($order, 'delivered');
                $delivered++;
            }
        }

        return $delivered;
    }
}
