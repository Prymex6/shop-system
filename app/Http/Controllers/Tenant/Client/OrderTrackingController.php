<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use App\Services\ProductRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrderTrackingController extends Controller
{
    public static function generateToken(string $orderNumber): string
    {
        return hash_hmac('sha256', $orderNumber, config('app.key'));
    }

    public function show(Request $request, string $orderNumber)
    {
        $token = $request->query('token');
        $query = Order::with(['items.product', 'items.variant', 'shippingMethod', 'downloadLinks.file'])
            ->where('order_number', $orderNumber);

        if ($token) {
            $order = $query->firstOrFail();
            $tokenValid = $order->tracking_token && hash_equals($order->tracking_token, (string) $token);
            $legacyTokenValid = hash_equals(self::generateToken($orderNumber), (string) $token);
            $isStaff = Auth::guard('tenant')->check();
            $customer = Auth::guard('customer')->user();
            $isOwner = $customer && $order->customer_id && $customer->id === $order->customer_id;

            if (!$tokenValid && !$legacyTokenValid && !$isOwner && !$isStaff) {
                abort(403);
            }
        } else {
            $customer = Auth::guard('customer')->user();
            $isStaff = Auth::guard('tenant')->check();

            if (!$customer && !$isStaff) {
                abort(404);
            }
            if ($customer && !$isStaff) {
                $query->where('customer_id', $customer->id);
            }
            $order = $query->firstOrFail();
        }

        return Inertia::render('Tenant/Client/OrderTracking', [
            'order' => $order,
            'upsellProducts' => $this->getUpsellProducts($order),
        ]);
    }

    /**
     * Post-purchase upsell: this page is the app's only real "thank you"
     * page, but never offered anything beyond what was already bought. True
     * one-click re-billing (charging the same order without re-entering
     * payment) isn't feasible here — the configured gateways (Przelewy24/
     * PayU/Tpay) are redirect-based with no saved-card/off-session charge
     * support — so this is "add to a fresh cart, checkout again" rather
     * than a literal single click, which the frontend CTA is honest about.
     */
    private function getUpsellProducts(Order $order): array
    {
        if (in_array($order->status, ['cancelled', 'refunded'], true)) {
            return [];
        }

        $recommendations = app(ProductRecommendationService::class);
        $purchasedProductIds = $order->items->pluck('product_id')->filter()->unique();

        $suggestions = collect();
        foreach ($order->items as $item) {
            if (!$item->product) {
                continue;
            }
            $suggestions = $suggestions->merge($recommendations->getFrequentlyBoughtTogether($item->product, 4));
        }

        $suggestions = $suggestions->unique('id')->reject(fn ($p) => $purchasedProductIds->contains($p->id));

        if ($suggestions->count() < 4) {
            $extra = $recommendations->getBestsellers(8)
                ->reject(fn ($p) => $purchasedProductIds->contains($p->id) || $suggestions->pluck('id')->contains($p->id));
            $suggestions = $suggestions->merge($extra);
        }

        return $suggestions->take(4)->values()->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'slug' => $p->slug,
            'type' => $p->type,
            'price' => (float) $p->price,
            'image' => $p->images->first()?->path,
        ])->all();
    }
}
