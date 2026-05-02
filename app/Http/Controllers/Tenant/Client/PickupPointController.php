<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ShippingMethod;
use App\Services\Shipping\InPostGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The parcel lockers a customer can pick from while checking out.
 */
class PickupPointController extends Controller
{
    public function __construct(private readonly InPostGateway $inPost) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_method_id' => ['required', 'integer', 'exists:shipping_methods,id'],
            'city' => ['required', 'string', 'max:100'],
        ]);

        $method = ShippingMethod::find($validated['shipping_method_id']);

        // Asking for lockers against a courier method is a sign the page and
        // the shop's settings have drifted apart, not something to answer.
        if (!$method || !$method->is_active || !$method->requiresPickupPoint()) {
            return response()->json(['points' => []]);
        }

        return response()->json([
            'points' => match ($method->carrier) {
                'inpost' => $this->inPost->points($validated['city']),
                default => [],
            },
        ]);
    }
}
