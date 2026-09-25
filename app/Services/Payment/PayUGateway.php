<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Events\OrderCreated;
use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenPayU_Configuration;
use OpenPayU_Exception;
use OpenPayU_Order;
use OpenPayU_Refund;

/**
 * PayU Gateway – uses official openpayu/openpayu SDK.
 */
class PayUGateway implements PaymentGatewayInterface
{
    protected bool $configured = false;

    public function __construct()
    {
        $posId = Setting::get('payu_pos_id', '');
        $signatureKey = Setting::get('payu_signature_key', '');
        $clientId = Setting::get('payu_client_id', '');
        $clientSecret = Setting::get('payu_client_secret', '');
        $mode = Setting::get('payu_mode', 'sandbox');

        if (!empty($posId) && !empty($signatureKey) && !empty($clientId) && !empty($clientSecret)) {
            $env = $mode === 'production' ? 'secure' : 'sandbox';
            OpenPayU_Configuration::setEnvironment($env);
            OpenPayU_Configuration::setMerchantPosId($posId);
            OpenPayU_Configuration::setSignatureKey($signatureKey);
            OpenPayU_Configuration::setOauthClientId($clientId);
            OpenPayU_Configuration::setOauthClientSecret($clientSecret);
            $this->configured = true;
        }
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function getName(): string
    {
        return 'PayU';
    }

    public function createPayment(Order $order): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => __('messages.payu_not_configured_for_shop')];
        }

        $amount = (int) round($order->total * 100); // grosze

        $orderData = [
            'notifyUrl' => route('tenant.payment.webhook.payu'),
            'customerIp' => request()->ip() ?: '127.0.0.1',
            'merchantPosId' => OpenPayU_Configuration::getMerchantPosId(),
            'description' => __('messages.payment_order_description', ['number' => $order->order_number]),
            'currencyCode' => 'PLN',
            'totalAmount' => $amount,
            'extOrderId' => $order->order_number,
            'buyer' => [
                'email' => $order->customer_email ?: 'noreply@example.com',
                'firstName' => $order->customer_name ? explode(' ', $order->customer_name)[0] : 'Klient',
                'lastName' => $order->customer_name ? (explode(' ', $order->customer_name)[1] ?? '') : '',
                'language' => 'pl',
            ],
            'products' => [[
                'name' => __('messages.payment_order_description', ['number' => $order->order_number]),
                'unitPrice' => $amount,
                'quantity' => 1,
            ]],
            'continueUrl' => route('tenant.payment.return', $order->order_number) . '?token=' . $order->tracking_token,
        ];

        try {
            $response = OpenPayU_Order::create($orderData);

            if ($response->getStatus() === 'SUCCESS') {
                $payuOrderId = $response->getResponse()->orderId;
                $redirectUri = $response->getResponse()->redirectUri;

                $order->update(['payment_data' => ['payu_order_id' => $payuOrderId, 'ext_order_id' => $order->order_number]]);

                return ['success' => true, 'payment_url' => $redirectUri];
            }

            Log::error('PayU Order Create Failed', ['status' => $response->getStatus()]);

            return ['success' => false, 'error' => __('messages.payu_create_failed', ['status' => $response->getStatus()])];

        } catch (OpenPayU_Exception $e) {
            Log::error('PayU Exception', ['message' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function handleReturn(Request $request, Order $order): bool
    {
        // PayU sends status via webhook; on return just check current order status
        $order->refresh();

        return $order->payment_status === 'paid';
    }

    public function handleWebhook(array $data): bool
    {
        try {
            $result = OpenPayU_Order::consumeNotification($data);
            $response = $result->getResponse();
            $status = $response->order->status ?? null;
            $extId = $response->order->extOrderId ?? null;

            if (!$extId) {
                Log::error('PayU Webhook: Missing extOrderId');

                return false;
            }

            $order = Order::where('order_number', $extId)->first();
            if (!$order) {
                Log::error('PayU Webhook: Order not found', ['ext_order_id' => $extId]);

                return false;
            }

            if ($status === 'COMPLETED' && $order->payment_status !== 'paid') {
                $paymentData = $order->payment_data ?? [];
                $order->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'status' => 'confirmed',
                    'payment_data' => array_merge($paymentData, ['payu_status' => $status, 'verified_at' => now()->toDateTimeString()]),
                ]);
                Log::info('PayU: payment confirmed', ['order_number' => $order->order_number, 'total' => $order->total]);
                event(new OrderCreated($order->fresh()));

                return true;
            }

            if ($status === 'CANCELED') {
                $order->update(['payment_status' => 'failed']);
            }

            return true; // Acknowledge webhook

        } catch (OpenPayU_Exception $e) {
            Log::error('PayU Webhook Exception', ['message' => $e->getMessage()]);

            return false;
        }
    }

    public function refund(Order $order, float $amount): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => __('messages.payu_not_configured')];
        }

        $paymentData = $order->payment_data ?? [];
        $payuOrderId = $paymentData['payu_order_id'] ?? null;

        if (!$payuOrderId) {
            return ['success' => false, 'error' => 'Brak danych transakcji PayU (orderId).'];
        }

        $amountInPennies = (int) round($amount * 100);

        try {
            $response = OpenPayU_Refund::create(
                $payuOrderId,
                __('messages.payment_refund_description', ['number' => $order->order_number]),
                $amountInPennies
            );

            if ($response->getStatus() === 'SUCCESS') {
                $refundId = $response->getResponse()->refund->refundId ?? null;
                Log::info('PayU Refund success', ['order' => $order->order_number, 'refund_id' => $refundId]);

                return ['success' => true, 'refund_id' => $refundId];
            }

            Log::error('PayU Refund failed', ['order' => $order->order_number, 'status' => $response->getStatus()]);

            return ['success' => false, 'error' => __('messages.payu_refund_failed', ['status' => $response->getStatus()])];
        } catch (OpenPayU_Exception $e) {
            Log::error('PayU Refund Exception', ['message' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
