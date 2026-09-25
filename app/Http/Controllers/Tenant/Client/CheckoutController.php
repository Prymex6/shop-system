<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Events\OrderCreated;
use App\Http\Controllers\Controller;
use App\Mail\Tenant\DownloadLinkMail;
use App\Mail\Tenant\NewOrderNotificationMail;
use App\Mail\Tenant\OrderConfirmedMail;
use App\Models\Tenant\DiscountCode;
use App\Models\Tenant\FlashSale;
use App\Models\Tenant\GiftCard;
use App\Models\Tenant\LoyaltyReward;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBundle;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\Setting;
use App\Models\Tenant\ShippingMethod;
use App\Services\DigitalDeliveryService;
use App\Services\FlashSaleService;
use App\Services\FraudDetectionService;
use App\Services\GiftCardService;
use App\Services\InventoryService;
use App\Services\LoyaltyService;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\ShippingCalculatorService;
use App\Services\TaxCalculatorService;
use App\Services\VolumeDiscountService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function __construct(
        protected ShippingCalculatorService $shippingCalculator,
        protected InventoryService $inventoryService,
        protected TaxCalculatorService $taxCalculator,
        protected LoyaltyService $loyaltyService,
        protected DigitalDeliveryService $digitalDelivery,
        protected FraudDetectionService $fraudDetection,
        protected FlashSaleService $flashSaleService,
        protected VolumeDiscountService $volumeDiscountService,
        protected GiftCardService $giftCardService,
    ) {}

    public function cart()
    {
        return $this->index();
    }

    public function index()
    {
        if (Setting::get('vacation_mode', false)) {
            return redirect()->route('tenant.shop')->with('info', Setting::get('vacation_message', 'Sklep jest chwilowo niedostępny.'));
        }

        $shippingMethods = ShippingMethod::where('is_active', true)->orderBy('sort_order')->get();

        $paymentMethods = [];

        if (Setting::get('payment_cash_on_delivery_enabled', false)) {
            $paymentMethods[] = ['value' => 'cash_on_delivery', 'label' => 'Płatność przy odbiorze (gotówka)', 'icon' => 'fa-money-bill-wave', 'type' => 'offline'];
        }
        if (Setting::get('payment_bank_transfer_enabled', false)) {
            $paymentMethods[] = ['value' => 'bank_transfer', 'label' => 'Przelew bankowy', 'icon' => 'fa-building-columns', 'type' => 'offline'];
        }

        $configuredGateways = PaymentGatewayFactory::configuredGateways();
        foreach ($configuredGateways as $method => $meta) {
            $settingKey = $meta['setting_key'] ?? "payment_{$method}_enabled";
            if (Setting::get($settingKey, false)) {
                $paymentMethods[] = [
                    'value' => $method,
                    'label' => $meta['label'],
                    'description' => $meta['description'],
                    'icon' => $meta['logo'],
                    'color' => $meta['color'],
                    'type' => 'online',
                ];
            }
        }

        $loyaltyOptions = null;
        if (Setting::get('loyalty_enabled', false) && Auth::guard('customer')->check()) {
            $customer = Auth::guard('customer')->user();
            $loyaltyOptions = $this->loyaltyService->calculateCheckoutOptions($customer, 0);
        }

        // Order bump: a single merchant-picked product offered as a one-click
        // add right before payment (Settings > Orders > "Product offered
        // at checkout"). Only offered while still published/active - the setting
        // can otherwise point at a product the merchant later unpublished.
        $orderBumpProduct = null;
        if ($orderBumpProductId = Setting::get('order_bump_product_id')) {
            $product = Product::published()->find($orderBumpProductId);
            if ($product) {
                $orderBumpProduct = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'type' => $product->type,
                    'price' => (float) $product->price,
                    'compare_price' => $product->compare_price ? (float) $product->compare_price : null,
                    'image' => $product->images->first()?->path,
                ];
            }
        }

        return Inertia::render('Tenant/Client/Checkout', [
            'shippingMethods' => $shippingMethods,
            'paymentMethods' => $paymentMethods,
            'loyaltyOptions' => $loyaltyOptions,
            'orderBumpProduct' => $orderBumpProduct,
        ]);
    }

    /**
     * Validate discount code (AJAX)
     */
    public function validateDiscountCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $code = strtoupper(trim($request->code));
        $discountCode = DiscountCode::where('code', $code)->first();

        if (!$discountCode || !$discountCode->is_active) {
            return response()->json(['valid' => false, 'message' => 'Kod rabatowy nie istnieje lub jest nieaktywny'], 422);
        }

        if ($discountCode->max_uses && $discountCode->used_count >= $discountCode->max_uses) {
            return response()->json(['valid' => false, 'message' => __('messages.discount_exhausted')], 422);
        }

        $validation = $discountCode->canBeUsed($request->subtotal);
        if (!$validation['valid']) {
            return response()->json(['valid' => false, 'message' => $validation['message']], 422);
        }

        $discountAmount = $discountCode->calculateDiscount($request->subtotal);

        return response()->json([
            'valid' => true,
            'message' => __('messages.discount_applied'),
            'discount' => [
                'id' => $discountCode->id,
                'code' => $discountCode->code,
                'type' => $discountCode->type,
                'value' => $discountCode->value,
                'amount' => $discountAmount,
                'formatted_amount' => number_format($discountAmount, 2, ',', ' ') . ' PLN',
            ],
        ]);
    }

    /**
     * Calculate shipping cost (AJAX)
     */
    public function calculateShipping(Request $request)
    {
        $request->validate([
            'shipping_method_id' => ['required', 'integer'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'country' => ['nullable', 'string', 'max:100'],
        ]);

        $method = ShippingMethod::find($request->shipping_method_id);
        $cost = $method ? $this->shippingCalculator->calculateCost($method, $request->input('country', 'PL'), $request->subtotal) : null;

        if ($cost === null) {
            return response()->json(['valid' => false, 'message' => __('messages.shipping_method_unavailable')], 422);
        }

        return response()->json([
            'valid' => true,
            'cost' => $cost,
            'free' => $cost === 0.0,
        ]);
    }

    public function store(Request $request)
    {
        if (Setting::get('vacation_mode', false)) {
            return response()->json(['success' => false, 'message' => __('messages.shop_unavailable')], 422);
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            // An SMS confirmation goes out to whatever is submitted here, unconditionally
            // — the format check keeps a checkout submission from being usable to spam an
            // arbitrary phone number with junk text (it must still look like a phone number).
            'customer_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{7,30}$/'],
            'shipping_method_id' => ['nullable', 'integer', 'exists:shipping_methods,id'],
            'shipping_address' => ['nullable', 'array'],
            'shipping_address.street' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['nullable', 'string', 'max:100'],
            'shipping_address.postcode' => ['nullable', 'string', 'max:20'],
            'shipping_address.country' => ['nullable', 'string', 'max:100'],
            // A locker code the carrier will reject is caught when the label
            // is bought; the address beside it is only ever shown back to the
            // customer, so it is bounded rather than trusted.
            'pickup_point_code' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/'],
            'pickup_point_data' => ['nullable', 'array'],
            'pickup_point_data.name' => ['nullable', 'string', 'max:150'],
            'pickup_point_data.street' => ['nullable', 'string', 'max:255'],
            'pickup_point_data.city' => ['nullable', 'string', 'max:100'],
            'pickup_point_data.postCode' => ['nullable', 'string', 'max:20'],
            'billing_address' => ['nullable', 'array'],
            'payment_method' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
            'discount_code' => ['nullable', 'string'],
            'gift_card_code' => ['nullable', 'string'],
            'loyalty_reward_id' => ['nullable', 'integer'],
            'loyalty_points_redeem' => ['nullable', 'integer', 'min:1'],
            'terms_accepted' => ['required', 'accepted'],
            'items' => ['required', 'array', 'min:1'],
            // A cart entry is either a plain product OR a bundle, never both —
            // ProductBundle::show() is the only place a bundle_id cart entry
            // can originate from (see BundleController).
            'items.*.product_id' => ['required_without:items.*.bundle_id', 'nullable', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.bundle_id' => ['required_without:items.*.product_id', 'nullable', 'integer', 'exists:product_bundles,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $customer = Auth::guard('customer')->user();

        // Plan's max_orders_per_month was only ever displayed on the License
        // page — nothing actually blocked placing an order past it, so a
        // tenant downgraded below their current volume (or simply outgrowing
        // a free/starter plan) saw no effect at all. Landlord\Tenant declares
        // its own 'central' connection, so its 'plan' relation can be loaded
        // directly without switching tenancy context (LicenseController's
        // tenancy()->end()/initialize() dance is unnecessary here and risky
        // inside checkout — if it failed between end() and initialize(),
        // every subsequent tenant-DB query in this method would break).
        // Failure here must never block a real sale, so any lookup error is
        // swallowed and checkout proceeds.
        try {
            $limit = tenancy()->tenant?->plan?->max_orders_per_month;
            if ($limit !== null) {
                $tz = Setting::get('timezone', 'Europe/Warsaw');
                $monthStart = Carbon::now($tz)->startOfMonth()->setTimezone('UTC');
                $monthEnd = Carbon::now($tz)->endOfMonth()->setTimezone('UTC');
                $ordersThisMonth = Order::whereBetween('created_at', [$monthStart, $monthEnd])->count();

                if ($ordersThisMonth >= $limit) {
                    return response()->json([
                        'success' => false,
                        'message' => __('messages.plan_order_limit'),
                    ], 422);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Plan order-limit check failed, allowing checkout: ' . $e->getMessage());
        }

        // Double-submit guard: the only client-side protection was a local
        // isSubmitting flag in Checkout.vue, defeated by two open tabs, the
        // browser back button after redirecting to the payment gateway (bfcache
        // can restore isSubmitting=false), or a plain network retry. Product::
        // lockForUpdate() below only prevents overselling — if stock covers
        // both requests, both pass and create two fully valid orders. Cache::add()
        // is atomic (tenant-scoped automatically via CacheTenancyBootstrapper),
        // so a second identical submission within the window is rejected
        // outright instead of silently creating a duplicate order.
        $checkoutFingerprint = hash('sha256', json_encode([
            'customer' => $customer?->id ?? $validated['customer_email'],
            'items' => collect($validated['items'])->sortBy(fn ($i) => 'p' . ($i['product_id'] ?? '') . '-b' . ($i['bundle_id'] ?? ''))->values()->all(),
            'method' => $validated['payment_method'],
            'shipping' => $validated['shipping_method_id'] ?? null,
        ]));
        $checkoutLockKey = 'checkout_lock_' . $checkoutFingerprint;

        if (!Cache::add($checkoutLockKey, true, now()->addSeconds(120))) {
            return response()->json([
                'success' => false,
                'message' => __('messages.order_already_placed'),
            ], 429);
        }

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $taxTotal = 0;
            $hasPhysical = false;
            $hasDigital = false;
            $orderItems = [];
            $usedFlashSaleIds = [];

            // EU OSS VAT — TaxCalculatorService previously always used the flat
            // rate assigned to the product, regardless of the customer's
            // country, even though the manager panel has a full "Add OSS rate"
            // form (TaxController::storeOss()) suggesting the shop already
            // handles this. It didn't: nothing read the customer's country or
            // ever queried a stored OSS rate. Resolved once here, before the
            // pricing loop, from shipping (falls back to billing for a
            // digital-only order with no shipping address at all).
            $customerCountry = $validated['shipping_address']['country']
                ?? $validated['billing_address']['country']
                ?? null;

            // Bundles were fully configurable in the manager panel but had no
            // storefront purchase path at all. Rather than teach the pricing/
            // stock/tax loop below a second code path, a bundle cart entry is
            // expanded here into one entry per real component product —
            // everything from here on (locking, stock checks, tax calc, flash
            // sale exclusion) is the exact same logic already used for plain
            // products, just fed pre-expanded input. Each expanded entry
            // carries a proportional unit price (see below) so the components
            // sum to the bundle's own price, not the sum of their individual
            // list prices — and _bundle_unit_price further down skips flash-
            // sale/volume-discount lookups for these entries, since a bundle
            // price is the merchant's own fixed offer, not meant to stack with
            // an unrelated, independently-configured promotion.
            $expandedItems = [];
            foreach ($validated['items'] as $item) {
                if (empty($item['bundle_id'])) {
                    $expandedItems[] = $item;

                    continue;
                }

                $bundle = ProductBundle::with('items.product', 'items.variant')->find($item['bundle_id']);
                if (!$bundle || !$bundle->is_active || $bundle->items->isEmpty()) {
                    throw new \Exception('Jeden z zestawów w koszyku nie jest już dostępny. Odśwież koszyk i spróbuj ponownie.');
                }

                $bundleQty = (int) $item['quantity'];

                $weights = $bundle->items->map(function ($bi) {
                    $unitPrice = $bi->variant ? (float) $bi->variant->price : (float) ($bi->product?->price ?? 0);

                    return $unitPrice * $bi->quantity;
                });
                $totalWeight = $weights->sum();

                foreach ($bundle->items as $index => $bundleItem) {
                    if (!$bundleItem->product) {
                        throw new \Exception("Zestaw '{$bundle->name}' zawiera produkt, który już nie istnieje. Skontaktuj się z obsługą.");
                    }

                    $weight = $weights[$index];
                    // Equal split if every component is nominally free (e.g. a
                    // promotional bonus item priced at 0) — avoids a division
                    // by zero while still charging the full bundle price
                    // across the components.
                    $shareOfBundlePrice = $totalWeight > 0
                        ? (float) $bundle->price * ($weight / $totalWeight)
                        : (float) $bundle->price / $bundle->items->count();
                    $unitPrice = $shareOfBundlePrice / $bundleItem->quantity;

                    $expandedItems[] = [
                        'product_id' => $bundleItem->product_id,
                        'variant_id' => $bundleItem->variant_id,
                        'quantity' => $bundleItem->quantity * $bundleQty,
                        '_bundle_id' => $bundle->id,
                        '_bundle_name' => $bundle->name,
                        '_bundle_unit_price' => round($unitPrice, 2),
                    ];
                }
            }

            // Lock products in a consistent global order (by product_id)
            // regardless of the order items were added to the cart —
            // otherwise two customers checking out the same two products in
            // opposite cart order can lock them in opposite order and
            // deadlock (MySQL kills one transaction with a raw SQLSTATE[40001],
            // surfacing as a failed order instead of the customer just
            // waiting a moment for the other checkout to finish).
            $sortedItems = collect($expandedItems)->sortBy('product_id')->values()->all();

            foreach ($sortedItems as $item) {
                // Product has no SoftDeletes — if a manager hard-deletes a
                // product between "add to cart" and "place order",
                // findOrFail() throws ModelNotFoundException, which the
                // generic catch below used to surface verbatim ("No query
                // results for model [App\Models\Tenant\Product] {id}") to
                // the customer — a raw framework message leaking the model
                // namespace and record id, unlike the clean message already
                // used for a merely-deactivated product just below.
                $product = Product::lockForUpdate()->find($item['product_id']);
                if (!$product) {
                    throw new \Exception('Jeden z produktów w koszyku nie jest już dostępny. Odśwież koszyk i spróbuj ponownie.');
                }

                if (!$product->is_published) {
                    throw new \Exception("Produkt '{$product->name}' jest niedostępny.");
                }

                $variant = null;
                if (!empty($item['variant_id'])) {
                    $variant = ProductVariant::where('id', $item['variant_id'])
                        ->where('product_id', $product->id)
                        ->where('is_active', true)
                        ->first();
                    if (!$variant) {
                        throw new \Exception("Nieprawidłowy wariant produktu '{$product->name}'.");
                    }
                }

                // Quantity restrictions check — skipped for bundle-derived lines:
                // the component quantity comes from the bundle's own
                // configuration (ProductBundleItem::quantity), not a customer
                // choice, so a standalone product's min/max order quantity
                // (meant to gate ad-hoc purchases) doesn't apply to it.
                if (!isset($item['_bundle_id'])) {
                    if ($product->min_order_qty !== null && $item['quantity'] < $product->min_order_qty) {
                        throw new \Exception("Minimalna ilość dla {$product->name} to {$product->min_order_qty}");
                    }
                    if ($product->max_order_qty !== null && $item['quantity'] > $product->max_order_qty) {
                        throw new \Exception("Maksymalna ilość to {$product->max_order_qty} dla {$product->name}");
                    }
                }

                // Stock check
                if ($product->track_stock) {
                    $stockQty = $variant ? $variant->stock_quantity : $product->stock_quantity;
                    if ($stockQty <= 0) {
                        throw new \Exception("Produkt '{$product->name}' jest niedostępny (brak w magazynie).");
                    }
                    if ($stockQty < $item['quantity'] && !$product->allow_backorder) {
                        throw new \Exception("Produkt '{$product->name}' dostępny tylko w ilości {$stockQty} szt.");
                    }
                }

                // Price is always resolved server-side, never trusted from the client.
                // A product can be discounted by at most one automatic mechanism at a
                // time (flash sale or volume/quantity break) — whichever is cheaper —
                // on top of the variant/base price; a discount code is applied
                // separately, below, on the resulting subtotal.
                if (isset($item['_bundle_unit_price'])) {
                    // Bundle-derived line: price is already the component's
                    // proportional share of the bundle's own fixed price —
                    // flash sales/volume discounts are a separate, independent
                    // promotion mechanism and don't stack on top of it.
                    $price = $item['_bundle_unit_price'];
                } else {
                    $basePrice = $variant ? $variant->effectivePrice() : (float) $product->price;
                    $flashSale = $this->flashSaleService->getActiveForProduct($product);
                    $flashPrice = $flashSale?->calculatePrice($product);
                    $volumePrice = $this->volumeDiscountService->calculate($product, $item['quantity']);
                    $price = min($basePrice, $flashPrice ?? $basePrice, $volumePrice);

                    if ($flashSale && $flashPrice !== null && $price === $flashPrice) {
                        $usedFlashSaleIds[] = $flashSale->id;
                    }
                }

                $lineTotal = $price * $item['quantity'];
                $subtotal += $lineTotal;

                if ($product->isPhysical()) {
                    $hasPhysical = true;
                }
                if ($product->isDigital()) {
                    $hasDigital = true;
                }

                // Prices shown to customers are gross (VAT-inclusive) — extract the
                // VAT portion for bookkeeping instead of adding it on top a second time.
                $taxResult = $this->taxCalculator->calculateForProduct($product, $price, $item['quantity'], $customerCountry);
                $taxTotal += $taxResult['tax_amount'];

                $orderItems[] = [
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'bundle_id' => $item['_bundle_id'] ?? null,
                    'bundle_name' => $item['_bundle_name'] ?? null,
                    'name' => $product->name,
                    'sku' => $variant?->sku ?? $product->sku,
                    'variant_label' => $variant?->label(),
                    'product_type' => $product->type,
                    'price' => $price,
                    'quantity' => $item['quantity'],
                    'tax_rate' => $taxResult['rate'],
                    'total' => $lineTotal,
                ];
            }

            // Shipping — a physical order must have a shipping method that's
            // actually eligible for the resolved zone/country. Silently
            // falling back to free/no shipping on a missing or ineligible
            // method would let a customer waive shipping entirely (omit the
            // field) or pay a cheaper zone's rate for an expensive one
            // (submit that zone's method ID) — both now rejected instead.
            $shippingCost = 0;
            $shippingMethodId = null;
            $pickupPointCode = null;
            $pickupPointData = null;

            if ($hasPhysical) {
                $shippingCountry = $validated['shipping_address']['country'] ?? 'PL';
                $shippingMethod = !empty($validated['shipping_method_id'])
                    ? ShippingMethod::find($validated['shipping_method_id'])
                    : null;
                $cost = $shippingMethod
                    ? $this->shippingCalculator->calculateCost($shippingMethod, $shippingCountry, $subtotal)
                    : null;

                if ($cost === null) {
                    DB::rollBack();
                    Cache::forget($checkoutLockKey);

                    return response()->json([
                        'success' => false,
                        'message' => __('messages.shipping_method_not_for_address'),
                    ], 422);
                }

                // A parcel-locker method with no locker on it produces an
                // order nobody can buy a label for, discovered days later in
                // the manager panel rather than here.
                if ($shippingMethod->requiresPickupPoint() && blank($validated['pickup_point_code'] ?? null)) {
                    DB::rollBack();
                    Cache::forget($checkoutLockKey);

                    return response()->json([
                        'success' => false,
                        'message' => __('messages.choose_locker'),
                    ], 422);
                }

                $shippingCost = $cost;
                $shippingMethodId = $shippingMethod->id;
                $pickupPointCode = $shippingMethod->requiresPickupPoint()
                    ? $validated['pickup_point_code']
                    : null;
                $pickupPointData = $pickupPointCode ? ($validated['pickup_point_data'] ?? null) : null;
            }

            // Discount code
            $discount = 0;
            $discountCodeId = null;
            $discountCode = null;

            if (!empty($validated['discount_code'])) {
                $code = strtoupper(trim($validated['discount_code']));
                $discountCode = DiscountCode::where('code', $code)
                    ->active()->valid()->notExhausted()
                    ->lockForUpdate()->first();

                if ($discountCode) {
                    $validation = $discountCode->canBeUsed($subtotal);
                    if ($validation['valid']) {
                        $discount = $discountCode->calculateDiscount($subtotal);
                        $discountCodeId = $discountCode->id;
                    }
                }
            }

            // Loyalty
            $loyaltyDiscount = 0;
            $loyaltyRewardId = null;
            $loyaltyPointsSpent = 0;

            if ($customer && Setting::get('loyalty_enabled', false)) {
                if (!empty($validated['loyalty_reward_id'])) {
                    $reward = LoyaltyReward::find($validated['loyalty_reward_id']);
                    if ($reward && $reward->isAvailable() && $customer->loyalty_points >= $reward->cost_points) {
                        $loyaltyRewardId = $reward->id;
                        $loyaltyPointsSpent = $reward->cost_points;
                        $loyaltyDiscount = match ($reward->type) {
                            'fixed_discount' => min((float) $reward->value, $subtotal),
                            'percent_discount' => round($subtotal * ($reward->value / 100), 2),
                            'free_delivery' => $shippingCost,
                            default => 0,
                        };
                    }
                } elseif (!empty($validated['loyalty_points_redeem'])) {
                    $ptsPerPln = max(1, (int) Setting::get('loyalty_points_per_pln', 1));
                    $ptsToRedeem = min((int) $validated['loyalty_points_redeem'], $customer->loyalty_points);
                    // Enforced here, not just shown in the UI — a raw POST could
                    // otherwise redeem points for 100% of the order regardless of
                    // the merchant-configured cap.
                    $maxPercent = (int) Setting::get('loyalty_max_redeem_percent', 100);
                    $maxDiscount = round($subtotal * $maxPercent / 100, 2);
                    $loyaltyDiscount = min(floor($ptsToRedeem / $ptsPerPln), $subtotal, $maxDiscount);
                    $loyaltyPointsSpent = $loyaltyDiscount * $ptsPerPln;
                }
            }

            // Gift card
            $giftCardDiscount = 0;
            $giftCard = null;

            if (!empty($validated['gift_card_code'])) {
                $giftCard = GiftCard::where('code', strtoupper(trim($validated['gift_card_code'])))
                    ->lockForUpdate()->first();

                if ($giftCard && $giftCard->isValid()) {
                    $remainingAfterOtherDiscounts = max(0, $subtotal + $shippingCost - $discount - $loyaltyDiscount);
                    $giftCardDiscount = min((float) $giftCard->current_value, $remainingAfterOtherDiscounts);
                } else {
                    $giftCard = null;
                }
            }

            $total = max(0, $subtotal + $shippingCost - $discount - $loyaltyDiscount - $giftCardDiscount);

            // Create order
            $order = Order::create([
                'customer_id' => $customer?->id,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'customer_ip' => $request->ip(),
                'shipping_method_id' => $shippingMethodId,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'pickup_point_code' => $pickupPointCode,
                'pickup_point_data' => $pickupPointData,
                'billing_address' => $validated['billing_address'] ?? null,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'tax' => $taxTotal,
                'discount' => $discount + $loyaltyDiscount,
                'total' => $total,
                'currency' => Setting::get('currency', 'PLN'),
                'payment_method' => $validated['payment_method'],
                'payment_status' => in_array($validated['payment_method'], ['przelewy24', 'payu', 'tpay', 'stripe', 'online']) ? 'awaiting_payment' : 'pending',
                'status' => 'pending',
                'fulfillment_status' => 'unfulfilled',
                'notes' => $validated['notes'] ?? null,
                'discount_code_id' => $discountCodeId,
                'gift_card_id' => $giftCard?->id,
                'gift_card_discount' => $giftCardDiscount,
                'tracking_token' => Str::random(32),
            ]);

            // Create order items
            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            // Decrement stock — iterates the expanded items (post bundle
            // breakdown), not the raw client payload: a bundle entry in
            // $validated['items'] has product_id = null (bundle_id instead),
            // which Product::find(null) resolves to nothing, silently
            // skipping stock decrement for every bundle purchase entirely.
            foreach ($sortedItems as $item) {
                $product = Product::find($item['product_id']);
                if (!$product || !$product->track_stock) {
                    continue;
                }

                if (!empty($item['variant_id'])) {
                    $variant = ProductVariant::find($item['variant_id']);
                    if ($variant) {
                        $variant->decrement('stock_quantity', $item['quantity']);
                    }
                } else {
                    $product->decrement('stock_quantity', $item['quantity']);
                }
            }

            // Increment discount code usage
            if ($discountCode !== null) {
                $discountCode->incrementUsage();
            }

            // Redeem gift card (row-locked above, so this can't be double-spent
            // by a concurrent checkout using the same code).
            if ($giftCard && $giftCardDiscount > 0) {
                $this->giftCardService->redeem($giftCard, $giftCardDiscount);
            }

            // "First N orders" flash-sale caps only mean something if orders using
            // the sale price actually count against the limit.
            foreach (array_unique($usedFlashSaleIds) as $flashSaleId) {
                FlashSale::where('id', $flashSaleId)->increment('orders_count');
            }

            // Fraud Detection
            $fraudResult = $this->fraudDetection->check($order, $request);
            if ($fraudResult->action === 'block') {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => __('messages.order_failed'),
                ], 422);
            }
            if ($fraudResult->action === 'review') {
                $order->update(['status' => 'pending']);
                $this->fraudDetection->logFlags($order, $fraudResult);
            }

            // Loyalty redemption
            if ($customer && $loyaltyPointsSpent > 0) {
                if ($loyaltyRewardId) {
                    $reward = LoyaltyReward::find($loyaltyRewardId);
                    $this->loyaltyService->redeemReward($customer, $reward, $order);
                } else {
                    $this->loyaltyService->redeemPointsAsPln($customer, (int) $loyaltyPointsSpent, $order);
                }
            }

            DB::commit();

            Log::info('Checkout: order placed', [
                'order_number' => $order->order_number,
                'customer_email' => $order->customer_email,
                'total' => $order->total,
                'payment_method' => $order->payment_method,
            ]);

            $isOnlinePayment = in_array($validated['payment_method'], ['przelewy24', 'payu', 'tpay', 'stripe', 'online']);

            if (!$isOnlinePayment) {
                event(new OrderCreated($order));

                // Generate digital download links for non-payment orders paid on delivery
                if ($hasDigital) {
                    try {
                        $links = $this->digitalDelivery->generateForOrder($order);
                        if ($links->isNotEmpty() && $order->customer_email) {
                            Mail::to($order->customer_email)->queue(new DownloadLinkMail($order, $links));
                        }
                    } catch (\Exception $e) {
                        Log::warning('Digital delivery generation failed: ' . $e->getMessage());
                    }
                }
            }

            // Send confirmation email
            try {
                $order->load('items');
                $trackingUrl = route('tenant.order.tracking', $order->order_number) . '?token=' . $order->tracking_token;
                Mail::to($order->customer_email)->queue(new OrderConfirmedMail($order, $trackingUrl));
            } catch (\Exception $e) {
                Log::warning('Order confirmation email failed: ' . $e->getMessage());
            }

            // Notify shop owner
            $shopEmail = Setting::get('shop_email') ?? Setting::get('shop_email');
            if ($shopEmail) {
                try {
                    Mail::to($shopEmail)->queue(new NewOrderNotificationMail($order));
                } catch (\Exception $e) {
                    Log::warning('New order notification email failed: ' . $e->getMessage());
                }
            }

            if ($isOnlinePayment) {
                return response()->json([
                    'success' => true,
                    'order_number' => $order->order_number,
                    'redirect_to_payment' => true,
                    'payment_url' => route('tenant.payment.initiate', $order->order_number) . '?token=' . $order->tracking_token,
                ]);
            }

            return response()->json([
                'success' => true,
                'order_number' => $order->order_number,
                'tracking_token' => $order->tracking_token,
                'message' => __('messages.order_accepted'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            // A legitimate failure (stock/variant/quantity issue) shouldn't lock
            // the customer out of retrying for the full window — only a
            // successfully placed order or a fraud block keeps the lock held.
            Cache::forget($checkoutLockKey);

            return response()->json([
                'success' => false,
                'message' => __('messages.checkout_failed', ['reason' => $e->getMessage()]),
            ], 422);
        }
    }
}
