<?php

namespace App\Jobs;

use App\Mail\Tenant\ScheduledReportMail;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendScheduledReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public string $period = 'weekly', // 'weekly' or 'monthly'
    ) {}

    public function handle(): void
    {
        if (!Setting::get('weekly_report_enabled', false)) {
            return;
        }

        $shopEmail = Setting::get('shop_email');

        if (!$shopEmail) {
            Log::warning('SendScheduledReport: no shop_email configured');

            return;
        }

        [$dateFrom, $dateTo, $label] = $this->getDateRange();

        // Build stats
        $stats = $this->buildStats($dateFrom, $dateTo);

        try {
            Mail::to($shopEmail)->send(new ScheduledReportMail(
                stats: $stats,
                period: $label,
                dateFrom: $dateFrom->format('d.m.Y'),
                dateTo: $dateTo->format('d.m.Y'),
            ));

            Log::info('SendScheduledReport: report sent', [
                'period' => $this->period,
                'to' => $shopEmail,
            ]);
        } catch (\Throwable $e) {
            Log::error('SendScheduledReport: mail failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function getDateRange(): array
    {
        if ($this->period === 'monthly') {
            $from = Carbon::now()->subMonth()->startOfMonth();
            $to = Carbon::now()->subMonth()->endOfMonth();
            $label = 'miesięczny';
        } else {
            $from = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY);
            $to = Carbon::now()->subWeek()->endOfWeek(Carbon::SUNDAY);
            $label = 'tygodniowy';
        }

        return [$from, $to, $label];
    }

    protected function buildStats(Carbon $from, Carbon $to): array
    {
        $orders = Order::whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->where('payment_status', 'paid')
            ->get();

        $revenue = $orders->sum('total');
        $count = $orders->count();

        $newCustomers = Customer::whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->count();

        return [
            'orders_count' => $count,
            'revenue' => $revenue,
            'new_customers' => $newCustomers,
            'avg_order_value' => $count > 0 ? round($revenue / $count, 2) : 0,
        ];
    }
}
