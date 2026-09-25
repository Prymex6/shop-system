<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Events\OrderCreated;
use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Przelewy24 Gateway – uses P24 REST API v1 directly.
 * P24 does not provide an official PHP SDK.
 */
class Przelewy24Gateway implements PaymentGatewayInterface
{
    protected string $apiUrl;

    protected int $merchantId;

    protected int $posId;

    protected string $apiKey;

    protected string $crc;

    protected bool $configured = false;

    public function __construct()
    {
        $this->merchantId = (int) Setting::get('p24_merchant_id', 0);
        $this->posId = (int) Setting::get('p24_pos_id', $this->merchantId);
        $this->apiKey = (string) Setting::get('p24_api_key', '');
        $this->crc = (string) Setting::get('p24_crc', '');
        $mode = Setting::get('p24_mode', 'sandbox');
        $this->apiUrl = $mode === 'production'
            ? 'https://secure.przelewy24.pl'
            : 'https://sandbox.przelewy24.pl';
        $this->configured = !empty($this->merchantId) && !empty($this->apiKey) && !empty($this->crc);
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function getName(): string
    {
        return 'Przelewy24';
    }

    public function createPayment(Order $order): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => __('messages.p24_not_configured_for_shop')];
        }

        $amount = (int) round($order->total * 100);
        $sessionId = $order->order_number . '_' . uniqid('', true);

        $params = [
            'merchantId' => $this->merchantId,
            'posId' => $this->posId,
            'sessionId' => $sessionId,
            'amount' => $amount,
            'currency' => 'PLN',
            'description' => __('messages.payment_order_description', ['number' => $order->order_number]),
            'email' => $order->customer_email ?: 'noreply@example.com',
            'client' => $order->customer_name,
            'country' => 'PL',
            'language' => 'pl',
            'urlReturn' => route('tenant.payment.return', $order->order_number) . '?token=' . $order->tracking_token,
            'urlStatus' => route('tenant.payment.webhook'),
            'encoding' => 'UTF-8',
        ];
        $params['sign'] = hash('sha384', json_encode([
            'sessionId' => $sessionId,
            'merchantId' => $this->merchantId,
            'amount' => $amount,
            'currency' => 'PLN',
            'crc' => $this->crc,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        try {
            $response = Http::withBasicAuth($this->posId, $this->apiKey)
                ->post("{$this->apiUrl}/api/v1/transaction/register", $params);
            $data = $response->json();

            if (isset($data['data']['token'])) {
                $order->update(['payment_data' => ['p24_token' => $data['data']['token'], 'p24_session_id' => $sessionId]]);

                return ['success' => true, 'payment_url' => "{$this->apiUrl}/trnRequest/{$data['data']['token']}"];
            }

            Log::error('P24 Registration Failed', ['response' => $data]);

            return ['success' => false, 'error' => $data['error'] ?? __('messages.p24_register_failed')];

        } catch (\Exception $e) {
            Log::error('P24 Exception', ['message' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function handleReturn(Request $request, Order $order): bool
    {
        $orderId = $request->get('orderId');
        if (!$orderId) {
            return false;
        }
        $amount = (int) round($order->total * 100);

        return $this->verify($order, $orderId, $amount);
    }

    public function handleWebhook(array $data): bool
    {
        $receivedSign = $data['sign'] ?? '';
        $calculated = hash('sha384', json_encode([
            'sessionId' => $data['sessionId'] ?? '',
            'orderId' => (int) ($data['orderId'] ?? 0),
            'amount' => (int) ($data['amount'] ?? 0),
            'currency' => $data['currency'] ?? 'PLN',
            'crc' => $this->crc,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if ($receivedSign !== $calculated) {
            Log::error('P24 Webhook: Invalid signature');

            return false;
        }

        $order = Order::whereJsonContains('payment_data->p24_session_id', $data['sessionId'])->first();
        if (!$order) {
            Log::error('P24 Webhook: Order not found', ['session_id' => $data['sessionId']]);

            return false;
        }

        return $this->verify($order, (string) $data['orderId'], (int) $data['amount']);
    }

    public function refund(Order $order, float $amount): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => __('messages.p24_not_configured')];
        }

        $paymentData = $order->payment_data ?? [];
        $orderId = $paymentData['p24_order_id'] ?? null;
        $sessionId = $paymentData['p24_session_id'] ?? null;

        if (!$orderId || !$sessionId) {
            return ['success' => false, 'error' => 'Brak danych transakcji P24 (orderId/sessionId).'];
        }

        $amount = (int) round($amount * 100);
        $refundsUuid = Str::uuid()->toString();

        $body = [
            'requestId' => 'refund_' . $order->order_number . '_' . time(),
            'refundsUuid' => $refundsUuid,
            'refunds' => [[
                'orderId' => (int) $orderId,
                'sessionId' => $sessionId,
                'amount' => $amount,
                'description' => __('messages.payment_refund_description', ['number' => $order->order_number]),
            ]],
        ];

        try {
            $response = Http::withBasicAuth($this->posId, $this->apiKey)
                ->post("{$this->apiUrl}/api/v1/transaction/refund", $body);
            $data = $response->json();

            if ($response->successful() && isset($data['data'])) {
                Log::info('P24 Refund success', ['order' => $order->order_number, 'response' => $data]);

                return ['success' => true, 'refund_id' => $refundsUuid];
            }

            Log::error('P24 Refund failed', ['order' => $order->order_number, 'response' => $data]);

            return ['success' => false, 'error' => $data['error'] ?? __('messages.p24_refund_failed')];
        } catch (\Exception $e) {
            Log::error('P24 Refund exception', ['message' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function verify(Order $order, string $orderId, int $amount): bool
    {
        // Idempotency: P24 (and our own return-URL polling) can call this more
        // than once for the same payment — never re-apply "paid" or re-fire
        // OrderCreated (which triggers dropship/notification side effects) twice.
        if ($order->payment_status === 'paid') {
            return true;
        }

        $paymentData = $order->payment_data ?? [];
        $sessionId = $paymentData['p24_session_id'] ?? null;
        if (!$sessionId) {
            return false;
        }

        $params = [
            'merchantId' => $this->merchantId,
            'posId' => $this->posId,
            'sessionId' => $sessionId,
            'amount' => $amount,
            'currency' => 'PLN',
            'orderId' => (int) $orderId,
        ];
        $params['sign'] = hash('sha384', json_encode([
            'sessionId' => $sessionId,
            'orderId' => (int) $orderId,
            'amount' => $amount,
            'currency' => 'PLN',
            'crc' => $this->crc,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        try {
            $response = Http::withBasicAuth($this->posId, $this->apiKey)
                ->put("{$this->apiUrl}/api/v1/transaction/verify", $params);
            $data = $response->json();

            if (($data['data']['status'] ?? '') === 'success') {
                $order->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'status' => 'paid',
                    'payment_data' => array_merge($paymentData, ['p24_order_id' => $orderId, 'verified_at' => now()->toDateTimeString()]),
                ]);
                Log::info('P24: payment confirmed', ['order_number' => $order->order_number, 'total' => $order->total]);
                event(new OrderCreated($order->fresh()));

                return true;
            }
        } catch (\Exception $e) {
            Log::error('P24 Verify Exception', ['message' => $e->getMessage()]);
        }

        return false;
    }
}
