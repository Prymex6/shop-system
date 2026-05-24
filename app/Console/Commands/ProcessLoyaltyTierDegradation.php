<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Setting;
use Illuminate\Console\Command;

class ProcessLoyaltyTierDegradation extends Command
{
    protected $signature = 'loyalty:process-tier-degradation';

    protected $description = 'Downgrade loyalty tier for customers who have not made a purchase in a configured period';

    protected array $tiers = ['bronze', 'silver', 'gold', 'platinum', 'diamond'];

    public function handle(): int
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            try {
                if (!Setting::get('loyalty_enabled', false)) {
                    tenancy()->end();

                    continue;
                }

                $days = (int) Setting::get('loyalty_tier_degradation_days', 365);
                $cutoff = now()->subDays($days);

                // Customers whose last paid order was before cutoff (or no orders)
                $customers = Customer::where('loyalty_tier', '!=', 'bronze')
                    ->where(function ($q) use ($cutoff) {
                        $q->whereDoesntHave('orders', fn ($o) => $o->where('payment_status', 'paid'))
                            ->orWhereHas('orders', function ($o) use ($cutoff) {
                                $o->where('payment_status', 'paid')
                                    ->havingRaw('MAX(created_at) < ?', [$cutoff]);
                            });
                    })
                    ->get();

                $degraded = 0;

                foreach ($customers as $customer) {
                    $currentIndex = array_search($customer->loyalty_tier ?? 'bronze', $this->tiers);
                    if ($currentIndex !== false && $currentIndex > 0) {
                        $newTier = $this->tiers[$currentIndex - 1];
                        $customer->update(['loyalty_tier' => $newTier]);
                        $degraded++;
                    }
                }

                if ($degraded > 0) {
                    $this->line("[{$tenant->id}] Degraded tier for {$degraded} customer(s).");
                }
            } catch (\Throwable $e) {
                $this->error("[{$tenant->id}] Error: " . $e->getMessage());
            }

            tenancy()->end();
        }

        return self::SUCCESS;
    }
}
