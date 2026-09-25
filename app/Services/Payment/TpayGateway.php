<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Events\OrderCreated;
use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tpay\OpenApi\Api\TpayApi;
use Tpay\OpenApi\Utilities\Cache;
use Tpay\OpenApi\Utilities\CacheCertificateProvider;
use Tpay\OpenApi\Utilities\TpayException;
use Tpay\OpenApi\Webhook\JWSVerifiedPaymentNotification;

/**
 * Tpay Gateway – uses official tpay-com/tpay-openapi-php SDK.
 */
class TpayGateway implements PaymentGatewayInterface
{
    protected bool $configured = false;

    protected ?TpayApi $api = null;

    public function __construct(?TpayApi $api = null)
    {
        if ($api !== null) {
            // Injected (e.g. from tests)
            $this->api = $api;
            $this->configured = true;

            return;
        }

        $clientId = Setting::get('tpay_client_id', '');
        $clientSecret = Setting::get('tpay_client_secret', '');
        $mode = Setting::get('tpay_mode', 'sandbox');

        if (!empty($clientId) && !empty($clientSecret)) {
            $laravelCache = app('cache')->driver();
            $tpayCache = new Cache(null, $laravelCache);

            $this->api = new TpayApi($tpayCache, $clientId, $clientSecret, $mode === 'production');
            $this->configured = true;
        }
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function getName(): string
    {
        return 'Tpay';
    }

    public function createPayment(Order $order): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => __('messages.tpay_not_configured_for_shop')];
        }

        $amount = round($order->total, 2);

        $fields = [
            'amount' => $amount,
            'description' => __('messages.payment_order_description', ['number' => $order->order_number]),
            'payer' => [
                'email' => $order->customer_email ?: 'noreply@example.com',
                'name' => $order->customer_name ?: 'Klient',
                'phone' => $order->customer_phone ?: '',
            ],
            'callbacks' => [
                'payerUrls' => [
                    'success' => route('tenant.payment.return', $order->order_number) . '?token=' . $order->tracking_token,
                    'error' => route('tenant.payment.return', $order->order_number) . '?token=' . $order->tracking_token . '&error=1',
                ],
                'notification' => [
                    'url' => route('tenant.payment.webhook.tpay'),
                    'email' => Setting::get('shop_email', ''),
                ],
            ],
        ];

        try {
            $result = $this->api->transactions()->createTransaction($fields);

            $transactionId = $result->transactionId ?? null;
            $transactionUrl = $result->transactionPaymentUrl ?? null;

            if ($transactionId && $transactionUrl) {
                $order->update(['payment_data' => ['tpay_transaction_id' => $transactionId, 'ext_order_id' => $order->order_number]]);

                return ['success' => true, 'payment_url' => $transactionUrl];
            }

            Log::error('Tpay createTransaction Failed', ['result' => (array) $result]);

            return ['success' => false, 'error' => __('messages.tpay_create_failed')];

        } catch (\Exception $e) {
            Log::error('Tpay Exception', ['message' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function handleReturn(Request $request, Order $order): bool
    {
        if ($request->get('error')) {
            return false;
        }

        // Check via API
        $order->refresh();
        if ($order->payment_status === 'paid') {
            return true;
        }

        // Optionally poll API for status
        $paymentData = $order->payment_data ?? [];
        $transactionId = $paymentData['tpay_transaction_id'] ?? null;

        if ($transactionId && $this->api) {
            try {
                $txn = $this->api->transactions()->getTransactionById($transactionId);
                $status = $txn->status ?? null;

                if ($status === 'correct') {
                    $this->markPaid($order, $paymentData, $transactionId);

                    return true;
                }
            } catch (\Exception $e) {
                Log::error('Tpay handleReturn Exception', ['message' => $e->getMessage()]);
            }
        }

        return false;
    }

    /**
     * Handle Tpay webhook. $data is ignored on purpose — it comes straight from the
     * unauthenticated request body and must never be trusted. We independently verify
     * the JWS signature (and, for classic notifications, the MD5 checksum) against the
     * raw request before extracting anything from it.
     */
    public function handleWebhook(array $data): bool
    {
        try {
            $notification = $this->verifyIncomingNotification();
        } catch (TpayException $e) {
            Log::error('Tpay Webhook: signature verification failed', ['message' => $e->getMessage()]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Tpay Webhook: unexpected error during verification', ['message' => $e->getMessage()]);

            return false;
        }

        try {
            $transactionId = $notification->tr_id->getValue() ?? null;
            $status = $notification->tr_status->getValue() ?? null;

            if (!$transactionId) {
                Log::error('Tpay Webhook: verified notification is missing tr_id');

                return false;
            }

            $order = Order::whereJsonContains('payment_data->tpay_transaction_id', $transactionId)->first();

            if (!$order) {
                Log::error('Tpay Webhook: Order not found', ['transaction_id' => $transactionId]);

                return false;
            }

            if (in_array($status, ['TRUE', 'PAID'], true) && $order->payment_status !== 'paid') {
                $this->markPaid($order, $order->payment_data ?? [], $transactionId);

                return true;
            }

            if ($status === 'CHARGEBACK' && $order->payment_status === 'paid') {
                $order->update(['payment_status' => 'refunded']);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Tpay Webhook Exception', ['message' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Verifies the JWS signature (and, for the classic form-encoded notification, the
     * MD5 checksum against the merchant's notification secret) using Tpay's own SDK.
     * Reads the raw request directly (headers + body), never the pre-parsed $data array.
     *
     * @throws TpayException on any verification failure — callers must treat that as "reject".
     */
    private function verifyIncomingNotification()
    {
        $notificationSecret = (string) Setting::get('tpay_notification_secret', '');
        $mode = Setting::get('tpay_mode', 'sandbox');

        $laravelCache = app('cache')->driver();
        $tpayCache = new Cache(null, $laravelCache);
        $certificateProvider = new CacheCertificateProvider($tpayCache);

        $verifier = new JWSVerifiedPaymentNotification(
            $certificateProvider,
            $notificationSecret,
            $mode === 'production'
        );

        return $verifier->getNotification();
    }

    public function refund(Order $order, float $amount): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => __('messages.tpay_not_configured')];
        }

        $paymentData = $order->payment_data ?? [];
        $transactionId = $paymentData['tpay_transaction_id'] ?? null;

        if (!$transactionId) {
            return ['success' => false, 'error' => __('messages.tpay_transaction_missing')];
        }

        try {
            $result = $this->api->transactions()->createRefundByTransactionId(
                ['amount' => round($amount, 2)],
                $transactionId
            );

            $refundId = $result->refundId ?? $result->id ?? null;
            Log::info('Tpay Refund success', ['order' => $order->order_number, 'refund_id' => $refundId]);

            return ['success' => true, 'refund_id' => $refundId];
        } catch (\Exception $e) {
            Log::error('Tpay Refund Exception', ['message' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function markPaid(Order $order, array $paymentData, ?string $transactionId): void
    {
        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'status' => 'confirmed',
            'payment_data' => array_merge($paymentData, ['tpay_transaction_id' => $transactionId, 'verified_at' => now()->toDateTimeString()]),
        ]);
        Log::info('Tpay: payment confirmed', ['order_number' => $order->order_number, 'total' => $order->total, 'transaction_id' => $transactionId]);
        event(new OrderCreated($order->fresh()));
    }
}
