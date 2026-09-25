<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Resolve an order by order_number and verify the requester is allowed to
     * see it — via the tracking_token query param, as the order's own
     * customer, or as staff. order_number was chosen over the raw auto-
     * increment id specifically so this can't be enumerated.
     */
    private function resolveOrder(Request $request, string $orderNumber): Order
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        $token = (string) $request->query('token', '');
        $tokenValid = $token !== '' && $order->tracking_token && hash_equals($order->tracking_token, $token);

        $customer = Auth::guard('customer')->user();
        $isOwner = $customer && $order->customer_id && $customer->id === $order->customer_id;
        $isStaff = Auth::guard('tenant')->check();

        abort_unless($tokenValid || $isOwner || $isStaff, 403);

        return $order;
    }

    /**
     * Start a payment, picking the gateway from the order's payment_method.
     */
    public function initiate(Request $request, string $orderNumber)
    {
        $order = $this->resolveOrder($request, $orderNumber);

        if ($order->payment_status === 'paid') {
            return redirect()->route('tenant.shop')->with('info', __('messages.order_already_paid'));
        }

        $method = $order->payment_method;

        if ($method === 'cash_on_delivery') {
            return redirect()->route('tenant.shop')->with('error', __('messages.order_no_online_payment'));
        }

        try {
            $gateway = PaymentGatewayFactory::make($method);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('tenant.shop')->with('error', __('messages.unknown_payment_method'));
        }

        if (!$gateway->isConfigured()) {
            return redirect()->route('tenant.shop')->with('error', __('messages.gateway_not_configured', ['gateway' => $gateway->getName()]));
        }

        $result = $gateway->createPayment($order);

        if ($result['success']) {
            return redirect($result['payment_url']);
        }

        Log::error('Payment initiation failed', ['method' => $method, 'error' => $result['error'] ?? '?']);

        return redirect()->route('tenant.shop')->with('error', __('messages.payment_start_failed', ['reason' => $result['error'] ?? '']));
    }

    /**
     * The customer coming back from the payment gateway.
     */
    public function return(Request $request, string $orderNumber)
    {
        $order = $this->resolveOrder($request, $orderNumber);
        $method = $order->payment_method;

        try {
            $gateway = PaymentGatewayFactory::make($method);
        } catch (\InvalidArgumentException) {
            return $this->paymentResultPage(false, __('messages.payment_method_unknown'));
        }

        $success = $gateway->handleReturn($request, $order);

        if ($success) {
            // Guests (the common case for online-paid digital purchases) have
            // no session tying them to this order — without the tracking
            // token the tracking page 404s them immediately after paying,
            // with no way back to their download links.
            return redirect(route('tenant.order.tracking', $order->order_number) . '?token=' . $order->tracking_token);
        }

        return $this->paymentResultPage(false, __('messages.payment_verify_failed'));
    }

    /**
     * Webhook P24 (legacy + default)
     */
    public function webhook(Request $request)
    {
        Log::info('P24 Webhook Received', $request->all());
        $gateway = PaymentGatewayFactory::make('przelewy24');
        $success = $gateway->handleWebhook($request->all());

        return $success ? response('OK', 200) : response('ERROR', 400);
    }

    /**
     * Webhook PayU (IPN notification)
     */
    public function webhookPayU(Request $request)
    {
        Log::info('PayU Webhook Received', $request->all());
        $gateway = PaymentGatewayFactory::make('payu');
        $success = $gateway->handleWebhook($request->all());

        return $success ? response()->json(['status' => 'OK']) : response()->json(['status' => 'ERROR'], 400);
    }

    /**
     * Webhook Tpay
     */
    public function webhookTpay(Request $request)
    {
        Log::info('Tpay Webhook Received', $request->all());
        $gateway = PaymentGatewayFactory::make('tpay');
        $success = $gateway->handleWebhook($request->all());

        return $success ? response()->json(['result' => '1']) : response()->json(['result' => '0'], 400);
    }

    private function paymentResultPage(bool $success, string $message)
    {
        return Inertia::render('Tenant/Client/PaymentResult', [
            'success' => $success,
            'message' => $message,
        ]);
    }
}
