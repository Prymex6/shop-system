<?php

namespace App\Services;

use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class SmsService
{
    /** Per-recipient cap, independent of the per-IP checkout throttle — a handful
     *  of source IPs placing fake orders with a victim's real phone number would
     *  otherwise be able to SMS-bomb them at the merchant's cost. */
    private const MAX_PER_RECIPIENT_PER_HOUR = 5;

    /**
     * Send an SMS via SMSAPI.pl
     *
     * @param string $phone Recipient phone number (international format preferred)
     * @param string $message SMS message content
     */
    public function send(string $phone, string $message): bool
    {
        $token = Setting::get('smsapi_token');
        $sender = Setting::get('sms_sender_name', 'Sklep');

        if (!$token) {
            Log::warning('SmsService: smsapi_token is not configured.');

            return false;
        }

        if (!Setting::get('sms_enabled', false)) {
            return false;
        }

        // Normalize phone number: remove spaces, dashes
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        $rateLimitKey = 'sms:' . tenant('id') . ':' . $phone;
        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_PER_RECIPIENT_PER_HOUR)) {
            Log::warning('SmsService: per-recipient rate limit exceeded', ['phone' => $phone]);

            return false;
        }
        RateLimiter::hit($rateLimitKey, 3600);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get('https://api.smsapi.pl/sms.do', [
                    'to' => $phone,
                    'message' => $message,
                    'from' => $sender,
                    'format' => 'json',
                ]);

            if ($response->successful()) {
                $body = $response->json();

                // SMSAPI returns "count" on success
                if (isset($body['count']) && $body['count'] > 0) {
                    return true;
                }

                Log::warning('SmsService: unexpected response', ['body' => $body]);

                return false;
            }

            Log::warning('SmsService: HTTP error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('SmsService: exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Send order confirmation SMS.
     */
    public function sendOrderConfirmation(Order $order): bool
    {
        if (!$order->customer_phone) {
            return false;
        }

        $shopName = Setting::get('shop_name', __('messages.shop_name_fallback'));
        $message = __('messages.sms_order_placed', [
            'number' => $order->order_number,
            'shop' => $shopName,
            'total' => number_format($order->total, 2, ',', ' ') . ' ' . $order->currency,
        ]);

        return $this->send($order->customer_phone, $message);
    }

    /**
     * Send shipping notification SMS.
     */
    public function sendShippingNotification(Order $order): bool
    {
        if (!$order->customer_phone) {
            return false;
        }

        $shopName = Setting::get('shop_name', __('messages.shop_name_fallback'));
        $message = __('messages.sms_order_shipped', [
            'number' => $order->order_number,
            'shop' => $shopName,
        ]);

        if ($order->tracking_number) {
            $message .= __('messages.sms_tracking_number', ['number' => $order->tracking_number]);
            if ($order->tracking_carrier) {
                $message .= " ({$order->tracking_carrier})";
            }
        }

        return $this->send($order->customer_phone, $message);
    }
}
