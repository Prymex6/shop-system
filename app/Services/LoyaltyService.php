<?php

namespace App\Services;

use App\Mail\Tenant\LoyaltyBirthdayMail;
use App\Mail\Tenant\LoyaltyPointsExpiringMail;
use App\Mail\Tenant\LoyaltyTierUpgradeMail;
use App\Models\Tenant\Customer;
use App\Models\Tenant\LoyaltyCampaign;
use App\Models\Tenant\LoyaltyPoint;
use App\Models\Tenant\LoyaltyRedemption;
use App\Models\Tenant\LoyaltyReward;
use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LoyaltyService
{
    const DEFAULT_TIERS = [
        'bronze' => ['min' => 0,     'name' => 'Brąz',    'color' => '#cd7f32', 'multiplier' => 1.0,  'monthly_bonus' => 0,    'delivery_bonus' => 0],
        'silver' => ['min' => 500,   'name' => 'Srebro',  'color' => '#9ca3af', 'multiplier' => 1.25, 'monthly_bonus' => 50,   'delivery_bonus' => 10],
        'gold' => ['min' => 1500,  'name' => 'Złoto',   'color' => '#f59e0b', 'multiplier' => 1.5,  'monthly_bonus' => 150,  'delivery_bonus' => 20],
        'platinum' => ['min' => 4000,  'name' => 'Platyna', 'color' => '#60a5fa', 'multiplier' => 2.0,  'monthly_bonus' => 400,  'delivery_bonus' => 999],
        'diamond' => ['min' => 10000, 'name' => 'Diament', 'color' => '#a78bfa', 'multiplier' => 3.0,  'monthly_bonus' => 1000, 'delivery_bonus' => 999],
    ];

    /**
     * Plan feature "loyalty_program" (LandlordSeeder — off on Starter) was
     * never checked here — only the manager's own `loyalty_enabled` Setting
     * was, so a plan not paying for loyalty still got full points-earning
     * once a manager flipped that toggle on. A lookup failure never blocks
     * awarding points a customer has actually earned.
     */
    private function planAllowsLoyalty(): bool
    {
        try {
            $plan = tenancy()->tenant?->plan;

            return !$plan || $plan->hasFeature('loyalty_program');
        } catch (\Exception $e) {
            Log::warning('Plan loyalty_program feature check failed, allowing: ' . $e->getMessage());

            return true;
        }
    }

    // -------------------------------------------------------------------------
    // EARNING
    // -------------------------------------------------------------------------

    public function awardPointsForOrder(Order $order): void
    {
        if (!Setting::get('loyalty_enabled', false)) {
            return;
        }
        if (!$this->planAllowsLoyalty()) {
            return;
        }
        if (!$order->customer_id) {
            return;
        }
        $cashMethods = ['cash_on_delivery'];
        $isPaid = $order->payment_status === 'paid'
            || (in_array($order->payment_method, $cashMethods) && $order->status === 'completed');
        if (!$isPaid) {
            return;
        }

        $customer = Customer::find($order->customer_id);
        if (!$customer) {
            return;
        }

        DB::transaction(function () use ($order, $customer) {
            $alreadyAwarded = LoyaltyPoint::where('order_id', $order->id)
                ->where('type', 'earned')
                ->lockForUpdate()
                ->exists();
            if ($alreadyAwarded) {
                return;
            }

            $basePoints = $this->calculateBasePoints($order);
            if ($basePoints <= 0) {
                return;
            }

            $tierConfig = $this->getTierConfig($customer->loyalty_tier ?? 'bronze');
            $multiplier = (float) ($tierConfig['multiplier'] ?? 1.0);

            $campaignMultiplier = $this->getActiveCampaignMultiplier($order);
            $multiplier = $multiplier * $campaignMultiplier;

            if ($this->isBirthdayMonth($customer)) {
                $birthdayMult = (float) Setting::get('loyalty_bonus_birthday_multiplier', 2.0);
                $multiplier = $multiplier * $birthdayMult;
            }

            $points = (int) floor($basePoints * $multiplier);

            $expiryDays = (int) Setting::get('loyalty_points_expiry_days', 0);
            $expiresAt = $expiryDays > 0 ? now()->addDays($expiryDays) : null;

            LoyaltyPoint::create([
                'customer_id' => $customer->id,
                'points' => $points,
                'type' => 'earned',
                'description' => 'Punkty za zamówienie #' . $order->order_number,
                'order_id' => $order->id,
                'expires_at' => $expiresAt,
            ]);

            $customer->increment('loyalty_points', $points);
            $customer->increment('loyalty_points_earned_total', $points);
            $this->updateTier($customer->fresh());

            Log::info('Loyalty: punkty przyznane za zamówienie', [
                'customer_id' => $customer->id,
                'order_number' => $order->order_number,
                'base_points' => $basePoints,
                'multiplier' => $multiplier,
                'campaign_mult' => $campaignMultiplier,
                'final_points' => $points,
                'birthday_month' => $this->isBirthdayMonth($customer),
            ]);
        });
    }

    private function calculateBasePoints(Order $order): int
    {
        $mode = Setting::get('loyalty_earn_mode', 'per_pln');

        if ($mode === 'per_pln') {
            $rate = max(1, (int) Setting::get('loyalty_points_per_pln', 1));

            return (int) floor($order->total * $rate);
        }

        if ($mode === 'per_order') {
            return max(0, (int) Setting::get('loyalty_points_per_order', 10));
        }

        if ($mode === 'tiered') {
            $tiers = Setting::get('loyalty_tiers');
            $tiers = $tiers ? (is_string($tiers) ? json_decode($tiers, true) : $tiers) : [];
            $points = 0;
            foreach ((array) $tiers as $tier) {
                if ($order->total >= (float) $tier['min_amount']) {
                    $points = (int) $tier['points'];
                }
            }

            return $points;
        }

        return (int) floor($order->total);
    }

    private function getActiveCampaignMultiplier(Order $order): float
    {
        $campaigns = LoyaltyCampaign::activeNow()->get();
        $maxMultiplier = 1.0;

        foreach ($campaigns as $campaign) {
            if ($campaign->applies_to === 'all') {
                $maxMultiplier = max($maxMultiplier, (float) $campaign->multiplier);
            } elseif ($campaign->applies_to === 'category') {
                $has = $order->items()
                    ->whereHas('product', fn ($q) => $q->where('category_id', $campaign->target_id))
                    ->exists();
                if ($has) {
                    $maxMultiplier = max($maxMultiplier, (float) $campaign->multiplier);
                }
            } elseif ($campaign->applies_to === 'product') {
                $has = $order->items()->where('product_id', $campaign->target_id)->exists();
                if ($has) {
                    $maxMultiplier = max($maxMultiplier, (float) $campaign->multiplier);
                }
            }
        }

        return $maxMultiplier;
    }

    private function isBirthdayMonth(Customer $customer): bool
    {
        if (!$customer->date_of_birth) {
            return false;
        }
        $tz = Setting::get('timezone', 'Europe/Warsaw');

        return $customer->date_of_birth->month === Carbon::now($tz)->month;
    }

    // -------------------------------------------------------------------------
    // REVOKING
    // -------------------------------------------------------------------------

    public function revokePointsForOrder(Order $order): void
    {
        if (!$order->customer_id) {
            return;
        }

        $earned = LoyaltyPoint::where('order_id', $order->id)->where('type', 'earned')->first();
        if (!$earned) {
            return;
        }

        $customer = Customer::find($order->customer_id);
        if (!$customer) {
            return;
        }

        LoyaltyPoint::create([
            'customer_id' => $customer->id,
            'points' => -$earned->points,
            'type' => 'revoked',
            'description' => 'Cofnięcie punktów – anulowane zamówienie #' . $order->order_number,
            'order_id' => $order->id,
        ]);

        $customer->update([
            'loyalty_points' => max(0, $customer->loyalty_points - $earned->points),
            'loyalty_points_earned_total' => max(0, $customer->loyalty_points_earned_total - $earned->points),
        ]);
        $this->updateTier($customer->fresh());

        Log::info('Loyalty: punkty cofnięte (anulowanie zamówienia)', [
            'customer_id' => $customer->id,
            'order_number' => $order->order_number,
            'points' => $earned->points,
        ]);
    }

    // -------------------------------------------------------------------------
    // REDEMPTION
    // -------------------------------------------------------------------------

    public function redeemReward(Customer $customer, LoyaltyReward $reward, ?Order $order = null): LoyaltyRedemption
    {
        if ($customer->loyalty_points < $reward->cost_points) {
            throw new \RuntimeException('Niewystarczająca liczba punktów.');
        }
        if (!$reward->isAvailable()) {
            throw new \RuntimeException('Nagroda jest niedostępna.');
        }

        return DB::transaction(function () use ($customer, $reward, $order) {
            // Re-read both rows with a lock to prevent double-spend AND
            // over-redemption of a limited-quantity reward under concurrent
            // requests — locking only the customer let two simultaneous
            // redemptions both pass isAvailable() while used_count was still
            // one below max_uses, pushing it past the configured limit.
            $fresh = Customer::lockForUpdate()->find($customer->id);
            $freshReward = LoyaltyReward::lockForUpdate()->find($reward->id);

            if (!$freshReward || !$freshReward->isAvailable()) {
                throw new \RuntimeException('Nagroda jest niedostępna.');
            }
            if (!$fresh || $fresh->loyalty_points < $freshReward->cost_points) {
                throw new \RuntimeException('Niewystarczająca liczba punktów.');
            }

            LoyaltyPoint::create([
                'customer_id' => $fresh->id,
                'points' => -$freshReward->cost_points,
                'type' => 'spent',
                'description' => 'Wymiana na nagrodę: ' . $freshReward->name,
                'order_id' => $order?->id,
            ]);

            $fresh->update(['loyalty_points' => max(0, $fresh->loyalty_points - $freshReward->cost_points)]);
            $freshReward->increment('used_count');

            Log::info('Loyalty: nagroda wymieniona', [
                'customer_id' => $fresh->id,
                'reward_id' => $freshReward->id,
                'reward_name' => $freshReward->name,
                'points_spent' => $freshReward->cost_points,
                'order_id' => $order?->id,
            ]);

            return LoyaltyRedemption::create([
                'customer_id' => $fresh->id,
                'reward_id' => $freshReward->id,
                'order_id' => $order?->id,
                'points_spent' => $freshReward->cost_points,
                'discount_value' => $freshReward->type !== 'free_product' ? $freshReward->value : 0,
                'reward_type' => $freshReward->type,
                'description' => $freshReward->name,
            ]);
        });
    }

    public function redeemPointsAsPln(Customer $customer, int $points, ?Order $order = null): float
    {
        $minPoints = (int) Setting::get('loyalty_min_redeem_points', 50);
        $rate = max(1, (int) Setting::get('loyalty_points_per_pln', 1));
        $discount = round($points / $rate, 2);

        DB::transaction(function () use ($customer, $points, $discount, $order) {
            // Re-read with lock to prevent double-spend under concurrent requests
            $fresh = Customer::lockForUpdate()->find($customer->id);
            if (!$fresh || $fresh->loyalty_points < $points) {
                throw new \RuntimeException('Niewystarczająca liczba punktów.');
            }

            $minPoints = (int) Setting::get('loyalty_min_redeem_points', 50);
            if ($points < $minPoints) {
                throw new \RuntimeException("Minimalna liczba punktów do wymiany: {$minPoints}.");
            }

            if ($order && $order->subtotal > 0) {
                $maxPercent = (int) Setting::get('loyalty_max_redeem_percent', 100);
                $maxDiscount = round((float) $order->subtotal * $maxPercent / 100, 2);
                if ($discount > $maxDiscount) {
                    throw new \RuntimeException('Przekroczono maksymalny procent zamówienia opłacany punktami.');
                }
            }

            LoyaltyPoint::create([
                'customer_id' => $fresh->id,
                'points' => -$points,
                'type' => 'spent',
                'description' => "Wymiana {$points} pkt na {$discount} PLN zniżki",
                'order_id' => $order?->id,
            ]);

            $fresh->update(['loyalty_points' => max(0, $fresh->loyalty_points - $points)]);

            Log::info('Loyalty: punkty wymienione na PLN', [
                'customer_id' => $fresh->id,
                'points' => $points,
                'discount' => $discount,
                'order_id' => $order?->id,
            ]);
        });

        return $discount;
    }

    // -------------------------------------------------------------------------
    // BONUSES
    // -------------------------------------------------------------------------

    public function awardRegistrationBonus(Customer $customer): void
    {
        if (!Setting::get('loyalty_enabled', false)) {
            return;
        }
        if (!$this->planAllowsLoyalty()) {
            return;
        }
        $points = (int) Setting::get('loyalty_bonus_registration', 50);
        if ($points <= 0) {
            return;
        }
        $this->addManualPoints($customer, $points, 'Bonus za rejestrację konta');
        Log::info('Loyalty: bonus rejestracyjny', ['customer_id' => $customer->id, 'points' => $points]);
    }

    public function awardFirstOrderBonus(Customer $customer): void
    {
        if (!Setting::get('loyalty_enabled', false)) {
            return;
        }
        if (!$this->planAllowsLoyalty()) {
            return;
        }
        $already = LoyaltyPoint::where('customer_id', $customer->id)->where('type', 'bonus_first_order')->exists();
        if ($already) {
            return;
        }
        $points = (int) Setting::get('loyalty_bonus_first_order', 100);
        if ($points <= 0) {
            return;
        }

        LoyaltyPoint::create([
            'customer_id' => $customer->id,
            'points' => $points,
            'type' => 'bonus_first_order',
            'description' => 'Bonus za pierwsze zamówienie',
        ]);
        $customer->increment('loyalty_points', $points);
        $customer->increment('loyalty_points_earned_total', $points);
        $this->updateTier($customer->fresh());
        Log::info('Loyalty: bonus za pierwsze zamówienie', ['customer_id' => $customer->id, 'points' => $points]);
    }

    public function awardReferralBonus(Customer $referrer): void
    {
        if (!Setting::get('loyalty_enabled', false)) {
            return;
        }
        if (!$this->planAllowsLoyalty()) {
            return;
        }
        $points = (int) Setting::get('loyalty_bonus_referral', 200);
        if ($points <= 0) {
            return;
        }
        $this->addManualPoints($referrer, $points, 'Bonus za polecenie znajomego');
        Log::info('Loyalty: bonus za polecenie', ['referrer_id' => $referrer->id, 'points' => $points]);
    }

    public function awardBirthdayBonus(Customer $customer): void
    {
        if (!$customer->date_of_birth) {
            return;
        }
        if (!Setting::get('loyalty_enabled', false)) {
            return;
        }
        if (!$this->planAllowsLoyalty()) {
            return;
        }
        $tz = Setting::get('timezone', 'Europe/Warsaw');
        $now = Carbon::now($tz);
        if ($customer->date_of_birth->month !== $now->month) {
            return;
        }

        if ($customer->loyalty_birthday_rewarded_at &&
            $customer->loyalty_birthday_rewarded_at->setTimezone($tz)->year === $now->year &&
            $customer->loyalty_birthday_rewarded_at->setTimezone($tz)->month === $now->month) {
            return;
        }

        $points = (int) Setting::get('loyalty_bonus_birthday_points', 100);
        if ($points <= 0) {
            return;
        }

        LoyaltyPoint::create([
            'customer_id' => $customer->id,
            'points' => $points,
            'type' => 'birthday',
            'description' => 'Bonus urodzinowy',
        ]);
        $customer->increment('loyalty_points', $points);
        $customer->increment('loyalty_points_earned_total', $points);
        $customer->update(['loyalty_birthday_rewarded_at' => now()]);
        $this->updateTier($customer->fresh());
        Log::info('Loyalty: bonus urodzinowy', ['customer_id' => $customer->id, 'points' => $points]);

        try {
            Mail::to($customer->email)->queue(new LoyaltyBirthdayMail($customer, $points));
        } catch (\Throwable) {
        }
    }

    public function awardMonthlyBonus(Customer $customer): void
    {
        if (!Setting::get('loyalty_enabled', false)) {
            return;
        }
        if (!$this->planAllowsLoyalty()) {
            return;
        }

        $tz = Setting::get('timezone', 'Europe/Warsaw');
        $now = Carbon::now($tz);
        $alreadyThisMonth = LoyaltyPoint::where('customer_id', $customer->id)
            ->where('type', 'bonus_monthly')
            ->whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->exists();
        if ($alreadyThisMonth) {
            return;
        }

        $tierConfig = $this->getTierConfig($customer->loyalty_tier ?? 'bronze');
        $points = (int) ($tierConfig['monthly_bonus'] ?? 0);
        if ($points <= 0) {
            return;
        }

        LoyaltyPoint::create([
            'customer_id' => $customer->id,
            'points' => $points,
            'type' => 'bonus_monthly',
            'description' => 'Miesięczny bonus poziomu ' . ($tierConfig['name'] ?? ''),
        ]);
        $customer->increment('loyalty_points', $points);
        $customer->increment('loyalty_points_earned_total', $points);
        $this->updateTier($customer->fresh());
        Log::info('Loyalty: miesięczny bonus', ['customer_id' => $customer->id, 'tier' => $customer->loyalty_tier, 'points' => $points]);
    }

    // -------------------------------------------------------------------------
    // EXPIRY
    // -------------------------------------------------------------------------

    public function expirePoints(): int
    {
        $expiryDays = (int) Setting::get('loyalty_points_expiry_days', 0);
        if ($expiryDays <= 0) {
            return 0;
        }

        $tz = Setting::get('timezone', 'Europe/Warsaw');
        $nowLocal = Carbon::now($tz)->format('Y-m-d H:i:s');

        $expiring = LoyaltyPoint::where('type', 'earned')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $nowLocal)
            ->where('points', '>', 0)
            ->get();

        $affected = 0;
        foreach ($expiring as $point) {
            $customer = $point->customer;
            if (!$customer) {
                continue;
            }

            LoyaltyPoint::create([
                'customer_id' => $customer->id,
                'points' => -$point->points,
                'type' => 'expired',
                'description' => 'Wygaśnięcie punktów z ' . $point->created_at->format('d.m.Y'),
                'order_id' => $point->order_id,
            ]);

            $customer->update(['loyalty_points' => max(0, $customer->loyalty_points - $point->points)]);
            $point->update(['expires_at' => null]);
            $affected++;

            Log::info('Loyalty: punkty wygasły', [
                'customer_id' => $customer->id,
                'points' => $point->points,
                'expired_at' => $point->created_at?->format('Y-m-d'),
            ]);
        }

        if ($affected > 0) {
            Log::info("Loyalty: wygasanie punktów zakończone — {$affected} wpisów przetworzono");
        }

        return $affected;
    }

    public function sendExpiryWarnings(int $warningDays = 14): void
    {
        $expiryDays = (int) Setting::get('loyalty_points_expiry_days', 0);
        if ($expiryDays <= 0) {
            return;
        }

        $tz = Setting::get('timezone', 'Europe/Warsaw');
        $nowLocal = Carbon::now($tz)->format('Y-m-d H:i:s');
        $threshold = Carbon::now($tz)->addDays($warningDays)->format('Y-m-d H:i:s');

        LoyaltyPoint::where('type', 'earned')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $threshold)
            ->where('expires_at', '>', $nowLocal)
            ->where('points', '>', 0)
            ->with('customer')
            ->get()
            ->groupBy('customer_id')
            ->each(function ($points) {
                $customer = $points->first()->customer;
                if (!$customer) {
                    return;
                }
                $totalExpiring = $points->sum('points');
                $earliest = $points->min('expires_at');
                try {
                    Mail::to($customer->email)->queue(
                        new LoyaltyPointsExpiringMail($customer, $totalExpiring, $earliest)
                    );
                } catch (\Throwable) {
                }
            });
    }

    // -------------------------------------------------------------------------
    // TIER MANAGEMENT
    // -------------------------------------------------------------------------

    public function updateTier(Customer $customer): void
    {
        $basis = Setting::get('loyalty_tier_basis', 'ever_earned');
        $score = $basis === 'ever_earned'
            ? ($customer->loyalty_points_earned_total ?? 0)
            : ($customer->loyalty_points ?? 0);

        $tiers = $this->getTiers();
        $newTier = 'bronze';
        foreach ($tiers as $key => $config) {
            if ($score >= (int) $config['min']) {
                $newTier = $key;
            }
        }

        if ($customer->loyalty_tier !== $newTier) {
            $oldTier = $customer->loyalty_tier ?? 'bronze';
            $customer->update(['loyalty_tier' => $newTier]);

            $tierKeys = array_keys($tiers);
            $isUpgrade = array_search($newTier, $tierKeys) > array_search($oldTier, $tierKeys);

            Log::info('Loyalty: zmiana poziomu', [
                'customer_id' => $customer->id,
                'old_tier' => $oldTier,
                'new_tier' => $newTier,
                'upgrade' => $isUpgrade,
            ]);

            if ($isUpgrade) {
                try {
                    Mail::to($customer->email)->queue(
                        new LoyaltyTierUpgradeMail($customer, $newTier, $this->getTierConfig($newTier))
                    );
                } catch (\Throwable) {
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // MANUAL
    // -------------------------------------------------------------------------

    public function addManualPoints(Customer $customer, int $points, string $description): void
    {
        LoyaltyPoint::create([
            'customer_id' => $customer->id,
            'points' => $points,
            'type' => 'manual',
            'description' => $description,
        ]);

        if ($points > 0) {
            $customer->increment('loyalty_points', $points);
            $customer->increment('loyalty_points_earned_total', $points);
        } else {
            $customer->update(['loyalty_points' => max(0, $customer->loyalty_points + $points)]);
        }

        $this->updateTier($customer->fresh());

        Log::info('Loyalty: ręczne punkty', [
            'customer_id' => $customer->id,
            'points' => $points,
            'description' => $description,
        ]);
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    public function getTiers(): array
    {
        $custom = Setting::get('loyalty_tiers');
        if ($custom) {
            $decoded = is_string($custom) ? json_decode($custom, true) : $custom;
            if (is_array($decoded) && count($decoded) > 0) {
                return $decoded;
            }
        }

        return self::DEFAULT_TIERS;
    }

    public function getTierConfig(string $tier): array
    {
        return $this->getTiers()[$tier] ?? self::DEFAULT_TIERS['bronze'];
    }

    public function getNextTier(string $currentTier): ?array
    {
        $tiers = $this->getTiers();
        $keys = array_keys($tiers);
        $idx = array_search($currentTier, $keys);
        if ($idx !== false && isset($keys[$idx + 1])) {
            $next = $keys[$idx + 1];

            return array_merge(['key' => $next], $tiers[$next]);
        }

        return null;
    }

    public function getPointsToNextTier(Customer $customer): ?int
    {
        $next = $this->getNextTier($customer->loyalty_tier ?? 'bronze');
        if (!$next) {
            return null;
        }
        $basis = Setting::get('loyalty_tier_basis', 'ever_earned');
        $score = $basis === 'ever_earned'
            ? ($customer->loyalty_points_earned_total ?? 0)
            : ($customer->loyalty_points ?? 0);

        return max(0, $next['min'] - $score);
    }

    public function calculateCheckoutOptions(Customer $customer, float $orderTotal): array
    {
        $redeemMode = Setting::get('loyalty_redeem_mode', 'rewards');
        $result = [
            'points_balance' => $customer->loyalty_points,
            'redeem_mode' => $redeemMode,
            'points_per_pln' => max(1, (int) Setting::get('loyalty_points_per_pln', 1)),
            'points_to_pln' => null,
            'available_rewards' => [],
        ];

        if (in_array($redeemMode, ['points_to_pln', 'both'])) {
            $rate = max(1, (int) Setting::get('loyalty_points_per_pln', 1));
            $maxPercent = (int) Setting::get('loyalty_max_redeem_percent', 30);
            $maxDiscount = round($orderTotal * $maxPercent / 100, 2);
            $maxPoints = (int) min($customer->loyalty_points, ceil($maxDiscount * $rate));
            $minPoints = (int) Setting::get('loyalty_min_redeem_points', 50);
            $result['points_to_pln'] = [
                'rate' => $rate,
                'min_points' => $minPoints,
                'max_points' => $maxPoints,
                'max_discount' => $maxDiscount,
            ];
        }

        if (in_array($redeemMode, ['rewards', 'both'])) {
            $result['available_rewards'] = LoyaltyReward::available()
                ->with(['product', 'productVariant'])
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'description' => $r->description,
                    'cost_points' => $r->cost_points,
                    'type' => $r->type,
                    'value' => $r->value,
                    'product_name' => $r->product?->name,
                    'product_image' => $r->product?->image,
                    'variant_name' => $r->productVariant?->name,
                    'affordable' => $customer->loyalty_points >= $r->cost_points,
                ])
                ->values()
                ->toArray();
        }

        return $result;
    }
}
