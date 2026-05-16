<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use App\Models\Tenant\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'order_id' => 'nullable|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:200',
            'body' => 'nullable|string|max:2000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'file|image|mimes:jpg,jpeg,png,webp|max:2048',
            'video_url' => 'nullable|url|max:500',
        ]);

        $existing = ProductReview::where('customer_id', $customer->id)
            ->where('product_id', $data['product_id'])
            ->exists();

        if ($existing) {
            return back()->with('error', __('messages.review_already_written'));
        }

        $isVerified = false;
        if (!empty($data['order_id'])) {
            $isVerified = Order::where('id', $data['order_id'])
                ->where('customer_id', $customer->id)
                ->whereHas('items', fn ($q) => $q->where('product_id', $data['product_id']))
                ->exists();
        }

        // Handle image uploads
        $uploadedImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('reviews', 'public');
                $uploadedImages[] = Storage::url($path);
            }
        }

        // Extract & remove non-model fields before create
        $reviewData = collect($data)->except(['images', 'video_url'])->toArray();

        ProductReview::create([
            ...$reviewData,
            'customer_id' => $customer->id,
            'reviewer_name' => $customer->name,
            'reviewer_email' => $customer->email,
            'is_verified_purchase' => $isVerified,
            'is_approved' => false,
            'images' => !empty($uploadedImages) ? $uploadedImages : null,
            'video_url' => $data['video_url'] ?? null,
        ]);

        return back()->with('success', __('messages.review_thanks'));
    }
}
