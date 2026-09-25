<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'tax_rate_id',
        'supplier_id',
        'name',
        'slug',
        'short_description',
        'description',
        'sku',
        'type',
        'price',
        'compare_price',
        'cost_price',
        'track_stock',
        'stock_quantity',
        'low_stock_threshold',
        'allow_backorder',
        'min_order_qty',
        'max_order_qty',
        'reorder_point',
        'reorder_quantity',
        'weight',
        'length',
        'width',
        'height',
        'download_limit',
        'download_expires_hours',
        'image',
        'image_width',
        'image_height',
        'gallery',
        'tags',
        'meta_title',
        'meta_description',
        'is_published',
        'is_featured',
        'sort_order',
        'reviews_count',
        'reviews_avg',
        'sales_count',
        'is_dropship',
        'dropship_supplier_id',
    ];

    // `isInStock()` (below) existed but nothing ever appended it to JSON — the
    // product page's "out of stock" badge read `product.is_in_stock`, which was
    // always undefined, so it showed "unavailable" on every single product
    // regardless of real stock state.
    protected $appends = ['is_in_stock'];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'gallery' => 'array',
        'tags' => 'array',
        'track_stock' => 'boolean',
        'allow_backorder' => 'boolean',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'stock_quantity' => 'integer',
        'reviews_avg' => 'decimal:2',
        'is_dropship' => 'boolean',
    ];

    // ─── Relationships ───────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function digitalFiles(): HasMany
    {
        return $this->hasMany(DigitalFile::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function collections()
    {
        return $this->belongsToMany(Collection::class, 'collection_products')
            ->withPivot('sort_order');
    }

    public function flashSales()
    {
        return $this->belongsToMany(FlashSale::class, 'flash_sale_products')
            ->withPivot('custom_price');
    }

    public function warehouseStock(): HasMany
    {
        return $this->hasMany(ProductWarehouseStock::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(ProductFeature::class)->orderBy('sort_order');
    }

    // ─── Scopes ──────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopePhysical($query)
    {
        return $query->where('type', 'physical');
    }

    public function scopeDigital($query)
    {
        return $query->where('type', 'digital');
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_stock', false)
                ->orWhere('stock_quantity', '>', 0)
                ->orWhere('allow_backorder', true);
        });
    }

    public function scopeLowStock($query)
    {
        return $query->where('track_stock', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    public function isPhysical(): bool
    {
        return $this->type === 'physical';
    }

    public function isDigital(): bool
    {
        return $this->type === 'digital';
    }

    public function isInStock(): bool
    {
        if (!$this->track_stock) {
            return true;
        }

        return $this->stock_quantity > 0 || $this->allow_backorder;
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->isInStock();
    }

    public function isLowStock(): bool
    {
        return $this->track_stock && $this->stock_quantity <= $this->low_stock_threshold && $this->stock_quantity > 0;
    }

    public function hasDiscount(): bool
    {
        return $this->compare_price && $this->compare_price > $this->price;
    }

    public function discountPercent(): int
    {
        if (!$this->hasDiscount()) {
            return 0;
        }

        return (int) round((($this->compare_price - $this->price) / $this->compare_price) * 100);
    }

    public function decrementStock(int $quantity = 1): void
    {
        if ($this->track_stock) {
            $this->decrement('stock_quantity', $quantity);
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }
}
