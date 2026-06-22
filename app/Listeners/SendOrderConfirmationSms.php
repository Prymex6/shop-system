<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmationSms implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(protected SmsService $smsService) {}

    public function handle(OrderCreated $event): void
    {
        try {
            $this->smsService->sendOrderConfirmation($event->order);
        } catch (\Throwable $e) {
            Log::warning('SendOrderConfirmationSms: failed', ['error' => $e->getMessage()]);
        }
    }
}
