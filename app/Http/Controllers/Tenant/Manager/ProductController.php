<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Category;
use App\Models\Tenant\DigitalFile;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductAttribute;
use App\Models\Tenant\ProductAttributeValue;
use App\Models\Tenant\ProductImage;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\TaxRate;
use App\Services\ProductImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProductController extends Controller
{
    // ─── Product CRUD ────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Product::with('category')
            ->when($request->search, fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->category, fn ($q) => $q->where('category_id', $request->category))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->status === 'published', fn ($q) => $q->where('is_published', true))
            ->when($request->status === 'draft', fn ($q) => $q->where('is_published', false))
            ->when($request->stock === 'low', fn ($q) => $q->lowStock())
            ->orderBy($request->sort ?? 'sort_order')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Tenant/Manager/Products/Index', [
            'products' => $query,
            'categories' => Category::active()->ordered()->get(['id', 'name']),
            'filters' => $request->only(['search', 'category', 'type', 'status', 'stock', 'sort']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Manager/Products/Form', [
            'product' => null,
            'categories' => Category::active()->ordered()->get(['id', 'name', 'parent_id']),
            'taxRates' => TaxRate::active()->get(['id', 'name', 'rate']),
            'attributes' => ProductAttribute::with('values')->get(),
        ]);
    }

    public function store(Request $request)
    {
        // Plan::max_products was never enforced anywhere — a tenant on a
        // limited plan could add unbounded products regardless of what
        // they're paying for. Landlord\Tenant declares its own 'central'
        // connection, so the 'plan' relation loads without switching
        // tenancy context (see CheckoutController::store()'s
        // max_orders_per_month check for the full reasoning). A lookup
        // failure never blocks product creation.
        try {
            $limit = tenancy()->tenant?->plan?->max_products;
            if ($limit !== null && Product::count() >= $limit) {
                return back()->withErrors(['name' => __('messages.product_limit_reached')]);
            }
        } catch (\Exception $e) {
            Log::warning('Plan product-limit check failed, allowing creation: ' . $e->getMessage());
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:physical,digital',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'tax_rate_id' => 'nullable|exists:tax_rates,id',
            'track_stock' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'allow_backorder' => 'boolean',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'download_limit' => 'nullable|integer|min:1',
            'download_expires_hours' => 'nullable|integer|min:1',
            'meta_title' => 'nullable|string|max:200',
            'meta_description' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer',
            'features' => 'nullable|array',
            'features.*.name' => 'required|string|max:150',
            'features.*.value' => 'required|string|max:500',
        ]);

        // Plan feature "digital_products" (LandlordSeeder — Starter plan has
        // it off) was seeded and shown in the landlord Plans form but never
        // actually checked anywhere tenant-side; a Starter-plan shop could
        // sell digital downloads exactly like a plan that pays for the
        // feature. A lookup failure never blocks product creation.
        if ($data['type'] === 'digital') {
            try {
                $plan = tenancy()->tenant?->plan;
                if ($plan && !$plan->hasFeature('digital_products')) {
                    return back()->withErrors(['type' => __('messages.digital_products_not_in_plan')]);
                }
            } catch (\Exception $e) {
                Log::warning('Plan digital_products feature check failed, allowing creation: ' . $e->getMessage());
            }
        }

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $features = $data['features'] ?? [];
        unset($data['features']);

        $product = Product::create($data);

        $this->syncFeatures($product, $features);

        // Upload primary image
        if ($request->hasFile('image')) {
            $request->validate(['image' => 'image|mimes:jpg,jpeg,png,webp|max:5120']);
            $result = app(ProductImageProcessor::class)->process($request->file('image'), 'public', 'products');
            $product->update([
                'image' => $result['path'],
                'image_width' => $result['width'],
                'image_height' => $result['height'],
            ]);
        }

        return redirect()->route('tenant.manager.products.edit', $product)->with('success', __('messages.product_created'));
    }

    public function edit(Product $product)
    {
        $product->load(['category', 'variants.product', 'images', 'digitalFiles', 'taxRate', 'features']);

        return Inertia::render('Tenant/Manager/Products/Form', [
            'product' => $product,
            'categories' => Category::active()->ordered()->get(['id', 'name', 'parent_id']),
            'taxRates' => TaxRate::active()->get(['id', 'name', 'rate']),
            'attributes' => ProductAttribute::with('values')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $product->id,
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:physical,digital',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'tax_rate_id' => 'nullable|exists:tax_rates,id',
            'track_stock' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'allow_backorder' => 'boolean',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'download_limit' => 'nullable|integer|min:1',
            'download_expires_hours' => 'nullable|integer|min:1',
            'meta_title' => 'nullable|string|max:200',
            'meta_description' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer',
            'features' => 'nullable|array',
            'features.*.name' => 'required|string|max:150',
            'features.*.value' => 'required|string|max:500',
        ]);

        // Only gate the transition INTO digital — an existing digital
        // product (created before a downgrade, or grandfathered in) must
        // stay editable, this only stops using edit to route around the
        // creation-time check above.
        if ($data['type'] === 'digital' && $product->type !== 'digital') {
            try {
                $plan = tenancy()->tenant?->plan;
                if ($plan && !$plan->hasFeature('digital_products')) {
                    return back()->withErrors(['type' => __('messages.digital_products_not_in_plan')]);
                }
            } catch (\Exception $e) {
                Log::warning('Plan digital_products feature check failed, allowing update: ' . $e->getMessage());
            }
        }

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $features = $data['features'] ?? [];
        unset($data['features']);

        $product->update($data);

        $this->syncFeatures($product, $features);

        if ($request->hasFile('image')) {
            $request->validate(['image' => 'image|mimes:jpg,jpeg,png,webp|max:5120']);
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $result = app(ProductImageProcessor::class)->process($request->file('image'), 'public', 'products');
            $product->update([
                'image' => $result['path'],
                'image_width' => $result['width'],
                'image_height' => $result['height'],
            ]);
        }

        return back()->with('success', __('messages.product_updated'));
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        foreach ($product->images as $image) {
            Storage::disk('public')->delete(array_filter([$image->path, $image->thumbnail_path]));
        }
        foreach ($product->digitalFiles as $file) {
            Storage::disk($file->disk)->delete($file->path);
        }

        $product->delete();

        return redirect()->route('tenant.manager.products.index')->with('success', __('messages.product_deleted'));
    }

    public function togglePublish(Product $product)
    {
        $product->update(['is_published' => !$product->is_published]);
        $status = $product->is_published ? 'opublikowany' : 'wycofany';

        return back()->with('success', "Produkt {$status}.");
    }

    // ─── Images ──────────────────────────────────────────────────────

    public function uploadImage(Request $request, Product $product)
    {
        $request->validate(['image' => 'required|image|max:5120']);

        $result = app(ProductImageProcessor::class)->process($request->file('image'), 'public', 'products');
        $order = $product->images()->max('sort_order') + 1;

        $image = $product->images()->create([
            'path' => $result['path'],
            'thumbnail_path' => $result['thumbnail_path'],
            'width' => $result['width'],
            'height' => $result['height'],
            'alt' => $request->alt ?? $product->name,
            'sort_order' => $order,
        ]);

        return response()->json(['image' => $image]);
    }

    public function destroyImage(Product $product, ProductImage $image)
    {
        Storage::disk('public')->delete(array_filter([$image->path, $image->thumbnail_path]));
        $image->delete();

        return response()->json(['ok' => true]);
    }

    public function reorderImages(Request $request, Product $product)
    {
        foreach ($request->order as $index => $id) {
            $product->images()->where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['ok' => true]);
    }

    // ─── Digital Files ───────────────────────────────────────────────

    // Digital products can legitimately be almost any binary format (PDF,
    // EPUB, ZIP, audio, video, images...), so a narrow mimes: allow-list
    // isn't practical — but without ANY mimes/mimetypes rule at all,
    // Laravel's built-in extension-spoofing guard (which only engages when
    // one of those rules is present) never ran, and a .php/.phtml/.phar
    // upload was accepted outright. Deny-list the executable extensions
    // instead, checked against the real (sniffed) extension Laravel
    // resolves, not the client-supplied one.
    private const DANGEROUS_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar',
        'exe', 'sh', 'bat', 'cmd', 'com', 'msi', 'cgi', 'pl',
        'htaccess', 'htpasswd', 'asp', 'aspx', 'jsp',
    ];

    public function uploadFile(Request $request, Product $product)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:102400', // 100MB
                function ($attribute, $value, $fail) {
                    $ext = strtolower($value->guessExtension() ?: $value->getClientOriginalExtension());
                    if (in_array($ext, self::DANGEROUS_EXTENSIONS, true)) {
                        $fail(__('messages.file_type_not_allowed'));
                    }
                },
            ],
        ]);

        $file = $request->file('file');
        $path = $file->store("digital/{$product->id}", 'local');
        $order = $product->digitalFiles()->max('sort_order') + 1;

        $digitalFile = $product->digitalFiles()->create([
            'name' => $request->name ?? $file->getClientOriginalName(),
            'path' => $path,
            'disk' => 'local',
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'sort_order' => $order,
        ]);

        return response()->json(['file' => $digitalFile]);
    }

    public function destroyFile(Product $product, DigitalFile $file)
    {
        Storage::disk($file->disk)->delete($file->path);
        $file->delete();

        return response()->json(['ok' => true]);
    }

    // ─── Variants ────────────────────────────────────────────────────

    public function variants(Product $product)
    {
        return Inertia::render('Tenant/Manager/Products/Variants', [
            'product' => $product->load('variants'),
            'attributes' => ProductAttribute::with('values')->get(),
        ]);
    }

    public function storeVariant(Request $request, Product $product)
    {
        $data = $request->validate([
            // Unlike products.sku (already unique), variant SKU had no
            // uniqueness check at all — a scanned barcode could match two
            // different physical variants in the system.
            'sku' => 'nullable|string|max:100|unique:product_variants,sku',
            'attributes' => 'required|array',
            'price' => 'nullable|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $this->guardAgainstDuplicateAttributeCombination($product, $data['attributes']);

        $variant = $product->variants()->create($data);

        return response()->json(['variant' => $variant]);
    }

    public function updateVariant(Request $request, Product $product, ProductVariant $variant)
    {
        abort_unless($variant->product_id === $product->id, 404);

        $data = $request->validate([
            'sku' => 'nullable|string|max:100|unique:product_variants,sku,' . $variant->id,
            'attributes' => 'required|array',
            'price' => 'nullable|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $this->guardAgainstDuplicateAttributeCombination($product, $data['attributes'], $variant->id);

        $variant->update($data);

        return response()->json(['variant' => $variant]);
    }

    /**
     * Two variants of the same product with an identical attribute
     * combination (e.g. two "Czerwony / L") aren't rejected anywhere —
     * VariantSelector.vue's Array.find() silently picks the first match,
     * making the second permanently unselectable in the storefront while
     * its stock sits orphaned with no visible way to sell it.
     */
    private function guardAgainstDuplicateAttributeCombination(Product $product, array $attributes, ?int $exceptVariantId = null): void
    {
        // Sort by key before comparing — {"Size":"L","Color":"Red"} and
        // {"Color":"Red","Size":"L"} represent the same combination but
        // encode differently without this.
        ksort($attributes);
        $normalized = json_encode($attributes);

        $existing = $product->variants()
            ->when($exceptVariantId, fn ($q) => $q->where('id', '!=', $exceptVariantId))
            ->get();

        foreach ($existing as $other) {
            $otherAttributes = $other->attributes ?? [];
            ksort($otherAttributes);
            if (json_encode($otherAttributes) === $normalized) {
                throw ValidationException::withMessages([
                    'attributes' => __('messages.variant_combination_exists'),
                ]);
            }
        }
    }

    public function destroyVariant(Product $product, ProductVariant $variant)
    {
        abort_unless($variant->product_id === $product->id, 404);

        $variant->delete();

        return response()->json(['ok' => true]);
    }

    // ─── Attributes ──────────────────────────────────────────────────

    public function attributes()
    {
        return Inertia::render('Tenant/Manager/Products/Attributes', [
            'attributes' => ProductAttribute::with('values')->get(),
        ]);
    }

    public function storeAttribute(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100']);
        $attr = ProductAttribute::create($data);

        return response()->json(['attribute' => $attr]);
    }

    public function updateAttribute(Request $request, ProductAttribute $attribute)
    {
        $data = $request->validate(['name' => 'required|string|max:100']);
        $attribute->update($data);

        return response()->json(['attribute' => $attribute]);
    }

    public function destroyAttribute(ProductAttribute $attribute)
    {
        $attribute->delete();

        return response()->json(['ok' => true]);
    }

    public function storeAttributeValue(Request $request, ProductAttribute $attribute)
    {
        $data = $request->validate([
            'value' => 'required|string|max:100',
            'color_hex' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'sort_order' => 'nullable|integer',
        ]);

        $value = $attribute->values()->create($data);

        return response()->json(['value' => $value]);
    }

    public function destroyAttributeValue(ProductAttribute $attribute, ProductAttributeValue $value)
    {
        $value->delete();

        return response()->json(['ok' => true]);
    }

    // ─── Features ────────────────────────────────────────────────────

    private function syncFeatures(Product $product, array $features): void
    {
        $product->features()->delete();

        foreach ($features as $i => $row) {
            if (empty(trim($row['name'])) || empty(trim($row['value']))) {
                continue;
            }
            $product->features()->create([
                'name' => trim($row['name']),
                'value' => trim($row['value']),
                'sort_order' => $i,
            ]);
        }
    }
}
