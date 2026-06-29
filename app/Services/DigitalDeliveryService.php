<?php

namespace App\Services;

use App\Models\Tenant\DigitalFile;
use App\Models\Tenant\DownloadLink;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DigitalDeliveryService
{
    /**
     * Generate download links for all digital items in an order.
     *
     * @return Collection<int, DownloadLink> the links just created (empty if
     *                                       the order has no digital items) — used by callers to decide
     *                                       whether to send the download-link email.
     */
    public function generateForOrder(Order $order): Collection
    {
        $created = new Collection;

        foreach ($order->items as $item) {
            if (!$item->isDigital()) {
                continue;
            }

            $product = $item->product;
            if (!$product) {
                continue;
            }

            $files = $product->digitalFiles;
            if ($files->isEmpty()) {
                // Create a single download link for the product itself (no files yet uploaded)
                $created->push($this->createLink($order, $item, $product, null));

                continue;
            }

            foreach ($files as $file) {
                $created->push($this->createLink($order, $item, $product, $file));
            }
        }

        return $created;
    }

    /**
     * Regenerate (invalidate old + create new) download links for an order.
     */
    public function regenerateForOrder(Order $order): Collection
    {
        $order->downloadLinks()->delete();

        return $this->generateForOrder($order);
    }

    /**
     * Find and validate a download link by token.
     */
    public function resolveToken(string $token): ?DownloadLink
    {
        $link = DownloadLink::where('token', $token)->with(['file', 'product'])->first();

        if (!$link || !$link->isValid()) {
            return null;
        }

        return $link;
    }

    /**
     * Record a download and return the file path for streaming.
     */
    public function recordDownload(DownloadLink $link): void
    {
        $link->incrementDownloads();
    }

    // ─── Private ─────────────────────────────────────────────────────

    // Applied only when the merchant hasn't set their own product-level value —
    // without these, an unconfigured product's download link was permanent
    // and unlimited-use by default (opt-out instead of opt-in), letting it be
    // freely shared/resold indefinitely.
    private const DEFAULT_EXPIRES_HOURS = 72;

    private const DEFAULT_DOWNLOAD_LIMIT = 5;

    private function createLink(Order $order, OrderItem $item, Product $product, ?DigitalFile $file): DownloadLink
    {
        $expiresAt = now()->addHours($product->download_expires_hours ?: self::DEFAULT_EXPIRES_HOURS);
        $downloadLimit = $product->download_limit ?: self::DEFAULT_DOWNLOAD_LIMIT;

        return DownloadLink::create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_id' => $product->id,
            'file_id' => $file?->id,
            'token' => Str::random(64),
            'expires_at' => $expiresAt,
            'download_limit' => $downloadLimit,
        ]);
    }
}
