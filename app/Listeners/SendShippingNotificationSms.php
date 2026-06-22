<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendShippingNotificationSms implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(protected SmsService $smsService) {}

    public function handle(OrderShipped $event): void
    {
        try {
            $this->smsService->sendShippingNotification($event->order);
        } catch (\Throwable $e) {
            Log::warning('SendShippingNotificationSms: failed', ['error' => $e->getMessage()]);
        }
    }
}
