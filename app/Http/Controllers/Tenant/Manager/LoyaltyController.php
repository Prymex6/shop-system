<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Customer;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoyaltyController extends Controller
{
    public function __construct(private LoyaltyService $loyalty) {}

    public function index(Request $request)
    {
        $query = Customer::withCount('orders')->orderByDesc('loyalty_points');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        if ($request->filled('tier')) {
            $query->where('loyalty_tier', $request->tier);
        }

        $customers = $query->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Manager/Loyalty/Index', [
            'customers' => $customers,
            'tiers' => $this->loyalty->getTiers(),
            'filters' => $request->only(['search', 'tier']),
        ]);
    }

    public function addPoints(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'points' => 'required|integer|not_in:0',
            'description' => 'required|string|max:255',
        ]);

        $this->loyalty->addManualPoints($customer, $validated['points'], $validated['description']);

        return back()->with('success', __(
            $validated['points'] > 0 ? 'messages.loyalty_points_added_for' : 'messages.loyalty_points_removed_for',
            ['customer' => $customer->name]
        ));
    }

    public function customerDetail(Customer $customer)
    {
        // Passed as separate props rather than via $customer->load(['loyaltyPoints' => ...])
        // — Eloquent snake-cases relation names when serializing, so an
        // eager-loaded "loyaltyPoints" relation becomes JSON key
        // "loyalty_points", silently overwriting the customer's real
        // loyalty_points integer balance column with the history array.
        $pointsHistory = $customer->loyaltyPoints()->latest()->limit(50)->get();
        $redemptions = $customer->loyaltyRedemptions()->with('reward')->latest()->get();

        return Inertia::render('Tenant/Manager/Loyalty/CustomerDetail', [
            'customer' => $customer,
            'pointsHistory' => $pointsHistory,
            'redemptions' => $redemptions,
            'tiers' => $this->loyalty->getTiers(),
            'tierInfo' => $this->loyalty->getTierConfig($customer->loyalty_tier ?? 'bronze'),
            'nextTier' => $this->loyalty->getNextTier($customer->loyalty_tier ?? 'bronze'),
            'pointsToNext' => $this->loyalty->getPointsToNextTier($customer),
        ]);
    }
}
