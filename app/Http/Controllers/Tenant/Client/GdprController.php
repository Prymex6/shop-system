<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ProductReview;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GdprController extends Controller
{
    /**
     * GET /moje-konto/gdpr/export
     * Export all customer data as JSON download.
     */
    public function export(): Response
    {
        $customer = Auth::guard('customer')->user();

        $data = [
            'profile' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'created_at' => $customer->created_at?->toIso8601String(),
            ],
            'addresses' => [
                'delivery_address' => $customer->delivery_address,
                'delivery_city' => $customer->delivery_city,
                'delivery_postal_code' => $customer->delivery_postal_code,
            ],
            'orders' => $customer->orders()
                ->with('items')
                ->latest()
                ->get()
                ->map(fn ($order) => [
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'total' => $order->total,
                    'currency' => $order->currency,
                    'created_at' => $order->created_at?->toIso8601String(),
                    'items' => $order->items->map(fn ($item) => [
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ])->toArray(),
                ])->toArray(),
            'loyalty_points' => [
                'current_balance' => $customer->loyalty_points,
                'total_earned' => $customer->loyalty_points_earned_total,
                'tier' => $customer->loyalty_tier,
                'history' => $customer->loyaltyPoints()->latest()->limit(100)->get()
                    ->map(fn ($p) => [
                        'type' => $p->type,
                        'points' => $p->points,
                        'created_at' => $p->created_at?->toIso8601String(),
                    ])->toArray(),
            ],
            'exported_at' => now()->toIso8601String(),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        AuditService::log('gdpr.export_downloaded', $customer);

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="moje-dane-' . $customer->id . '.json"',
        ]);
    }

    /**
     * POST /moje-konto/gdpr/delete-request
     * Request account deletion (sets flag for manual admin review).
     */
    public function deleteRequest(Request $request): JsonResponse
    {
        $customer = Auth::guard('customer')->user();

        $customer->update(['delete_requested_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => __('messages.deletion_request_recorded'),
        ]);
    }

    /**
     * POST /moje-konto/gdpr/anonymize
     * Anonymize the customer account (GDPR right to erasure).
     */
    public function anonymize(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'current_password:customer'],
        ]);

        $customer = Auth::guard('customer')->user();
        $uuid = (string) Str::uuid();

        $customer->update([
            'name' => 'Usunięty użytkownik',
            'email' => $uuid . '@deleted.invalid',
            'phone' => null,
            'password' => bcrypt(Str::random(32)),
            'delivery_address' => null,
            'delivery_city' => null,
            'delivery_postal_code' => null,
            'google_id' => null,
            'facebook_id' => null,
            'avatar' => null,
            'date_of_birth' => null,
            'referral_code' => null,
            'loyalty_referred_by' => null,
            'pending_email' => null,
            'email_change_token' => null,
            'email_change_token_expires_at' => null,
            'delete_requested_at' => now(),
        ]);

        // Erasure must reach every place this customer's personal data was copied
        // to at order time — anonymizing only the customer row leaves their real
        // name/email/phone/address sitting in every historical order and review.
        $customer->orders()->update([
            'customer_name' => 'Usunięty użytkownik',
            'customer_email' => $uuid . '@deleted.invalid',
            'customer_phone' => null,
            'shipping_address' => null,
            'billing_address' => null,
        ]);

        ProductReview::where('customer_id', $customer->id)->update([
            'reviewer_name' => 'Usunięty użytkownik',
            'reviewer_email' => null,
        ]);

        AuditService::log('gdpr.account_anonymized', $customer);

        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => __('messages.your_data_anonymised'),
        ]);
    }
}
