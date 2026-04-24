<?php

namespace App\Contracts;

use App\Models\Tenant\Order;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Check if gateway is configured (credentials set in settings)
     */
    public function isConfigured(): bool;

    /**
     * Get human-readable gateway name
     */
    public function getName(): string;

    /**
     * Initiate payment - returns payment_url or error
     * Result: ['success' => bool, 'payment_url' => string, 'error' => string]
     */
    public function createPayment(Order $order): array;

    /**
     * Handle return from gateway (user redirect back)
     * Returns true if payment is confirmed
     */
    public function handleReturn(Request $request, Order $order): bool;

    /**
     * Handle async webhook notification from gateway
     * Returns true if processed successfully
     */
    public function handleWebhook(array $data): bool;

    /**
     * Refund (fully or partially) a paid order through the gateway.
     * $amount is in the order's currency (e.g. PLN), not cents/grosze —
     * each implementation converts internally.
     * Result: ['success' => bool, 'refund_id' => ?string, 'error' => ?string]
     */
    public function refund(Order $order, float $amount): array;
}
