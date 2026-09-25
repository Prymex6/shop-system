<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\LoyaltyPoint;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductReview;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = ProductReview::with(['product', 'customer'])
            ->when($request->product, fn ($q) => $q->where('product_id', $request->product))
            ->when($request->rating, fn ($q) => $q->where('rating', $request->rating))
            ->when($request->status === 'pending', fn ($q) => $q->where('is_approved', false))
            ->when($request->status === 'approved', fn ($q) => $q->approved())
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Tenant/Manager/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => $request->only(['product', 'rating', 'status']),
        ]);
    }

    public function approve(ProductReview $review)
    {
        // A double-click or repeated request must not double-count the review
        // or double-award loyalty points.
        if ($review->is_approved) {
            return back()->with('success', __('messages.review_approved'));
        }

        $review->update(['is_approved' => true]);
        $review->product()->increment('reviews_count');
        $this->recalcAvg($review->product_id);

        // Award loyalty points for review if customer exists and loyalty enabled
        if ($review->customer_id && Setting::get('loyalty_enabled', false)) {
            $points = (int) Setting::get('loyalty_review_points', 10);
            if ($points > 0) {
                $review->customer->increment('loyalty_points', $points);
                $review->customer->increment('loyalty_points_earned_total', $points);
                LoyaltyPoint::create([
                    'customer_id' => $review->customer_id,
                    'points' => $points,
                    'type' => 'earned',
                    'description' => __('messages.loyalty_points_for_review', ['product' => $review->product->name]),
                ]);
            }
        }

        return back()->with('success', __('messages.review_approved'));
    }

    public function reject(ProductReview $review)
    {
        if ($review->is_approved) {
            $review->product()->decrement('reviews_count');
        }
        $review->update(['is_approved' => false]);
        $this->recalcAvg($review->product_id);

        return back()->with('success', __('messages.review_rejected'));
    }

    public function reply(Request $request, ProductReview $review)
    {
        $data = $request->validate(['reply' => 'required|string|max:1000']);
        $review->update(['reply' => $data['reply'], 'replied_at' => now()]);

        return back()->with('success', __('messages.reply_added'));
    }

    public function destroy(ProductReview $review)
    {
        if ($review->is_approved) {
            $review->product()->decrement('reviews_count');
        }
        $productId = $review->product_id;
        foreach ($review->images ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }
        $review->delete();
        $this->recalcAvg($productId);

        return back()->with('success', __('messages.review_deleted'));
    }

    private function recalcAvg(int $productId): void
    {
        $avg = ProductReview::where('product_id', $productId)->where('is_approved', true)->avg('rating') ?? 0;
        Product::where('id', $productId)->update(['reviews_avg' => $avg]);
    }
}
