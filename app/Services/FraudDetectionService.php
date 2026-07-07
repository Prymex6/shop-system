<?php

namespace App\Services;

use App\Models\Tenant\FraudBlocklist;
use App\Models\Tenant\FraudFlag;
use App\Models\Tenant\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FraudScore
{
    public function __construct(
        public int $score = 0,
        public array $flags = [],
        public string $action = 'allow', // 'allow' | 'review' | 'block'
    ) {}
}

class FraudDetectionService
{
    private const BLOCK_THRESHOLD = 80;

    private const REVIEW_THRESHOLD = 40;

    private const HIGH_VALUE_PLN = 2000;

    public function check(Order $order, Request $request): FraudScore
    {
        $score = 0;
        $flags = [];

        try {
            // 1. Check email blocklist
            if (FraudBlocklist::where('type', 'email')
                ->where('value', strtolower($order->customer_email))
                ->exists()) {
                $score += 100;
                $flags[] = 'email_blocklisted';
            }

            // 2. Check IP blocklist
            $ip = $request->ip();
            if ($ip && FraudBlocklist::where('type', 'ip')->where('value', $ip)->exists()) {
                $score += 100;
                $flags[] = 'ip_blocklisted';
            }

            // 3. IP placed >3 orders in 24h
            if ($ip) {
                $recentByIp = Order::where('created_at', '>=', now()->subHours(24))
                    ->where('customer_ip', $ip)
                    ->count();

                if ($recentByIp > 3) {
                    $score += 40;
                    $flags[] = 'ip_high_frequency';
                }
            }

            // 4. Email placed >3 orders in 24h
            $recentByEmail = Order::where('customer_email', $order->customer_email)
                ->where('created_at', '>=', now()->subHours(24))
                ->count();

            if ($recentByEmail > 3) {
                $score += 30;
                $flags[] = 'email_high_frequency';
            }

            // 5. High value order with no purchase history
            if ($order->total >= self::HIGH_VALUE_PLN) {
                $historyCount = Order::where('customer_email', $order->customer_email)
                    ->where('payment_status', 'paid')
                    ->count();

                if ($historyCount === 0) {
                    $score += 25;
                    $flags[] = 'high_value_no_history';
                }
            }

            // Determine action
            $action = match (true) {
                $score >= self::BLOCK_THRESHOLD => 'block',
                $score >= self::REVIEW_THRESHOLD => 'review',
                default => 'allow',
            };

            return new FraudScore($score, $flags, $action);
        } catch (\Exception $e) {
            Log::error('FraudDetection error: ' . $e->getMessage());

            return new FraudScore(0, [], 'allow');
        }
    }

    public function logFlags(Order $order, FraudScore $result): void
    {
        foreach ($result->flags as $flag) {
            FraudFlag::create([
                'order_id' => $order->id,
                'flag_type' => $flag,
                'score' => $result->score,
                'details' => [
                    'total_score' => $result->score,
                    'action' => $result->action,
                    'flags' => $result->flags,
                ],
            ]);
        }
    }
}
