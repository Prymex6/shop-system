<?php

namespace App\Services;

use App\Models\Tenant\Badge;
use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerBadge;
use App\Models\Tenant\ProductReview;

class BadgeService
{
    public function checkAndAward(Customer $customer): array
    {
        $awarded = [];
        $existingBadgeIds = $customer->badges()->pluck('badge_id')->toArray();

        $badges = Badge::all();

        foreach ($badges as $badge) {
            if (in_array($badge->id, $existingBadgeIds)) {
                continue;
            }

            if ($this->meetsCondition($customer, $badge)) {
                CustomerBadge::create([
                    'customer_id' => $customer->id,
                    'badge_id' => $badge->id,
                    'awarded_at' => now(),
                ]);
                $awarded[] = $badge;
            }
        }

        return $awarded;
    }

    protected function meetsCondition(Customer $customer, Badge $badge): bool
    {
        return match ($badge->condition_type) {
            'orders_count' => $customer->orders()->where('payment_status', 'paid')->count() >= $badge->condition_value,
            'total_spent' => (float) $customer->orders()->where('payment_status', 'paid')->sum('total') >= $badge->condition_value,
            'review_count' => ProductReview::where('customer_id', $customer->id)->where('is_approved', true)->count() >= $badge->condition_value,
            'referral_count' => Customer::where('loyalty_referred_by', $customer->id)->count() >= $badge->condition_value,
            default => false,
        };
    }
}
