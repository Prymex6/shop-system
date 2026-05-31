<?php

namespace App\Jobs;

use App\Mail\Tenant\WishlistAlertMail;
use App\Models\Tenant\Wishlist;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWishlistAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param 'price_drop'|'restock' $reason
     */
    public function __construct(public Wishlist $item, public string $reason) {}

    public function handle(): void
    {
        $item = $this->item->fresh(['customer', 'product']);

        if (!$item || !$item->customer || !$item->product) {
            return;
        }

        $alreadyNotified = $this->reason === 'price_drop' ? $item->price_drop_notified : $item->restock_notified;
        if ($alreadyNotified) {
            return;
        }

        if ($item->customer->marketing_opt_out) {
            return;
        }

        try {
            Mail::to($item->customer->email)->send(new WishlistAlertMail($item, $this->reason));

            $item->update($this->reason === 'price_drop'
                ? ['price_drop_notified' => true]
                : ['restock_notified' => true]);

            Log::info('WishlistAlert: notification sent', [
                'wishlist_id' => $item->id,
                'reason' => $this->reason,
                'email' => $item->customer->email,
            ]);
        } catch (\Throwable $e) {
            Log::warning('WishlistAlert: failed to send notification', [
                'wishlist_id' => $item->id,
                'reason' => $this->reason,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
