<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Customer;
use App\Models\Tenant\ProductReview;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GdprController extends Controller
{
    /**
     * GET /manager/gdpr
     * List customers who have requested data deletion.
     */
    public function requests(Request $request)
    {
        $customers = Customer::whereNotNull('delete_requested_at')
            ->latest('delete_requested_at')
            ->get();

        return Inertia::render('Tenant/Manager/Gdpr/Index', [
            'customers' => $customers,
        ]);
    }

    /**
     * POST /manager/gdpr/{customer}/anonymize
     * Fulfil a customer's erasure request on their behalf — this is the only
     * action on this page, and had no route/method at all (the "Anonimizuj"
     * button threw route-not-found), so staff could not actually complete a
     * GDPR erasure request from the admin panel. Mirrors the erasure fields
     * customers can already apply to themselves in
     * Tenant\Client\GdprController::anonymize().
     */
    public function anonymize(Customer $customer)
    {
        $uuid = (string) Str::uuid();

        $customer->update([
            'name' => __('messages.deleted_user'),
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
        ]);

        $customer->orders()->update([
            'customer_name' => __('messages.deleted_user'),
            'customer_email' => $uuid . '@deleted.invalid',
            'customer_phone' => null,
            'shipping_address' => null,
            'billing_address' => null,
        ]);

        ProductReview::where('customer_id', $customer->id)->update([
            'reviewer_name' => __('messages.deleted_user'),
            'reviewer_email' => null,
        ]);

        AuditService::log('gdpr.account_anonymized_by_staff', $customer);

        return back()->with('success', __('messages.customer_anonymised'));
    }
}
