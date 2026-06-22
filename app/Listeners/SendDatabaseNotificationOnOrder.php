<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\Tenant\User;
use App\Notifications\NewOrderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendDatabaseNotificationOnOrder implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderCreated $event): void
    {
        try {
            // Notify all managers
            $managers = User::where('role', 'manager')->where('is_active', true)->get();
            foreach ($managers as $manager) {
                $manager->notify(new NewOrderNotification($event->order));
            }
        } catch (\Exception $e) {
            Log::error('SendDatabaseNotificationOnOrder failed: ' . $e->getMessage());
        }
    }
}
