<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ProductReview;
use App\Services\TrustBadgeService;
use Inertia\Inertia;

class TrustController extends Controller
{
    public function __construct(protected TrustBadgeService $trustBadgeService) {}

    public function index()
    {
        $trustpilotUrl = $this->trustBadgeService->getTrustpilotUrl();
        $googleUrl = $this->trustBadgeService->getGoogleReviewsUrl();

        // If Trustpilot configured — redirect to it
        if ($trustpilotUrl) {
            return redirect()->away($trustpilotUrl);
        }

        // Otherwise show internal reviews page
        $reviews = ProductReview::with('product')
            ->where('status', 'approved')
            ->latest()
            ->paginate(20);

        return Inertia::render('Tenant/Client/Reviews', [
            'reviews' => $reviews,
            'googleUrl' => $googleUrl,
            'schema' => $this->trustBadgeService->getSchema(),
        ]);
    }
}
