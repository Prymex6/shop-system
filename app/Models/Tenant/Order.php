<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'status',
        'fulfillment_status',
        'customer_id',
        'discount_code_id',
        'shipping_method_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_ip',
        'shipping_address',
        'pickup_point_code',
        'pickup_point_data',
        'billing_address',
        'subtotal',
        'shipping_cost',
        'discount',
        'tax',
        'total',
        'currency',
        'payment_method',
        'payment_status',
        'payment_data',
        'paid_at',
        'tracking_number',
        'tracking_carrier',
        'shipped_at',
        'delivered_at',
        'download_token',
        'download_token_expires_at',
        'tracking_token',
        'invoice_number',
        'invoice_requested',
        'invoice_data',
        'notes',
        'internal_notes',
        'gift_card_id',
        'gift_card_discount',
    ];

    // Manager-only field (explicitly labeled "Tylko dla managera..." in the
    // UI) was serialized wholesale into the Inertia JSON payload sent to
    // the customer's own browser on both the order-tracking page and
    // "Moje konto" — readable via view-source/devtools even though no Vue
    // template rendered it. Not currently displayed anywhere in the
    // manager UI either; a future manager-facing page that needs it should
    // call ->makeVisible('internal_notes') explicitly rather than this
    // being visible-by-default everywhere.
    protected $hidden = [
        'internal_notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'download_token_expires_at' => 'datetime',
        'shipping_address' => 'array',
        'pickup_point_data' => 'array',
        'billing_address' => 'array',
        'payment_data' => 'array',
        'invoice_data' => 'array',
        'invoice_requested' => 'boolean',
        'gift_card_discount' => 'decimal:2',
    ];

    // ─── Relationships ───────────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function downloadLinks(): HasMany
    {
        return $this->hasMany(DownloadLink::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function rmaRequests(): HasMany
    {
        return $this->hasMany(RmaRequest::class);
    }

    public function fraudFlags(): HasMany
    {
        return $this->hasMany(FraudFlag::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeUnfulfilled($query)
    {
        return $query->where('fulfillment_status', 'unfulfilled')
            ->where('payment_status', 'paid');
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    public function hasDigitalItems(): bool
    {
        return $this->items()->where('product_type', 'digital')->exists();
    }

    public function hasPhysicalItems(): bool
    {
        return $this->items()->where('product_type', 'physical')->exists();
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function canBeRefunded(): bool
    {
        return in_array($this->payment_status, ['paid']) &&
               !in_array($this->status, ['refunded', 'cancelled']);
    }

    public function generateDownloadToken(): string
    {
        $token = Str::random(64);
        $this->update([
            'download_token' => $token,
            'download_token_expires_at' => now()->addDays(30),
        ]);

        return $token;
    }

    // ─── Boot ────────────────────────────────────────────────────────

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = DB::transaction(function () {
                    $tz = Setting::get('timezone', 'Europe/Warsaw');
                    $today = now()->setTimezone($tz)->format('ymd');
                    $prefix = 'ORD-' . $today . '-';

                    $last = DB::table('orders')
                        ->where('order_number', 'like', $prefix . '%')
                        ->lockForUpdate()
                        ->max('order_number');

                    $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

                    return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
                });
            }
        });
    }
}
