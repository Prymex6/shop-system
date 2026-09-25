<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use Illuminate\Http\Request;

class BulkProductController extends Controller
{
    /**
     * PATCH /manager/products/bulk
     * Bulk update a field on selected products.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['required', 'integer'],
            'field' => ['required', 'string', 'in:price,is_active,category_id,track_stock,stock_quantity,is_featured,is_published'],
            'value' => ['required'],
        ]);

        $allowedFields = [
            'price' => 'numeric|min:0',
            'is_active' => 'boolean',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'track_stock' => 'boolean',
            'stock_quantity' => 'integer|min:0',
            'category_id' => 'nullable|integer|exists:categories,id',
        ];

        // Validate value based on field
        $field = $data['field'];
        $value = $data['value'];

        // Coerce value types
        if (in_array($field, ['is_active', 'is_published', 'is_featured', 'track_stock'])) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } elseif (in_array($field, ['stock_quantity', 'category_id'])) {
            $value = $value !== null ? (int) $value : null;
        } elseif ($field === 'price') {
            $value = (float) $value;
        }

        // $allowedFields declared the real bounds (e.g. price/stock can't go
        // negative) but was never actually applied — coercion above happily
        // cast "-50" to a real negative float with nothing rejecting it.
        request()->validate(['value' => $allowedFields[$field]], [], ['value' => $field]);

        $count = Product::whereIn('id', $data['ids'])->update([$field => $value]);

        return back()->with('success', __('messages.products_updated_count', ['count' => $count]));
    }

    /**
     * DELETE /manager/products/bulk
     * Bulk delete selected products.
     */
    public function destroy(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer'],
        ]);

        $count = Product::whereIn('id', $data['ids'])->delete();

        return back()->with('success', __('messages.products_deleted_count', ['count' => $count]));
    }
}
