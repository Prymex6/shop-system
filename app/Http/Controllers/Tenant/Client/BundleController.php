<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ProductBundle;
use Inertia\Inertia;

/**
 * ProductBundle was fully configurable in the manager panel but had no
 * storefront presence at all — this is the page that makes a bundle
 * actually reachable and purchasable by a customer.
 */
class BundleController extends Controller
{
    public function show(ProductBundle $bundle)
    {
        if (!$bundle->is_active) {
            abort(404);
        }

        $bundle->load(['items.product', 'items.variant']);

        return Inertia::render('Tenant/Client/Shop/Bundle', [
            'bundle' => $bundle,
        ]);
    }
}
