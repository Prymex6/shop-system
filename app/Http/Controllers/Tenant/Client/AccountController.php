<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Mail\Tenant\EmailChangeMail;
use App\Models\Tenant\Customer;
use App\Models\Tenant\DownloadLink;
use App\Models\Tenant\LoyaltyReward;
use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use App\Services\InventoryService;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function index()
    {
        $customer = Auth::guard('customer')->user();

        $orders = $customer->orders()
            ->with(['items', 'shippingMethod'])
            ->latest()
            ->paginate(10);

        $downloads = DownloadLink::whereHas('order', fn ($q) => $q->where('customer_id', $customer->id))
            ->with(['file', 'order'])
            ->latest()
            ->get()
            ->map(fn ($link) => [
                'id' => $link->id,
                'token' => $link->token,
                'order_number' => $link->order->order_number,
                'product_name' => $link->file?->name,
                'download_count' => $link->download_count,
                'max_downloads' => $link->max_downloads,
                'expires_at' => $link->expires_at,
                'is_expired' => $link->expires_at && $link->expires_at->isPast(),
            ]);

        $loyaltyHistory = $customer->loyaltyPoints()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($p) => [
                'points' => $p->points,
                'type' => $p->type,
                'description' => $p->description,
                'created_at' => $p->created_at,
            ]);

        $loyaltyService = app(LoyaltyService::class);
        $nextTier = $loyaltyService->getNextTier($customer->loyalty_tier ?? 'bronze');
        $pointsToNext = $loyaltyService->getPointsToNextTier($customer);
        $tierInfo = $loyaltyService->getTierConfig($customer->loyalty_tier ?? 'bronze');

        $availableRewards = [];
        if (Setting::get('loyalty_enabled', false)) {
            $availableRewards = LoyaltyReward::available()
                ->with(['product', 'productVariant'])
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'description' => $r->description,
                    'cost_points' => $r->cost_points,
                    'type' => $r->type,
                    'value' => $r->value,
                    'product_name' => $r->product?->name,
                    'variant_name' => $r->productVariant?->name,
                    'affordable' => ($customer->loyalty_points ?? 0) >= $r->cost_points,
                ])->values();
        }

        $redemptionHistory = $customer->loyaltyRedemptions()
            ->with('reward')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'points_spent' => $r->points_spent,
                'discount_value' => $r->discount_value,
                'description' => $r->description,
                'reward_type' => $r->reward_type,
                'reward_name' => $r->reward?->name,
                'created_at' => $r->created_at,
            ]);

        return Inertia::render('Tenant/Client/Account', [
            'customer' => $customer->only('id', 'name', 'email', 'phone', 'avatar', 'loyalty_points', 'loyalty_tier', 'loyalty_points_earned_total', 'date_of_birth', 'referral_code'),
            'orders' => $orders,
            'downloads' => $downloads,
            'loyalty' => [
                'points' => $customer->loyalty_points ?? 0,
                'points_earned_total' => $customer->loyalty_points_earned_total ?? 0,
                'tier' => $customer->loyalty_tier ?? 'bronze',
                'tier_info' => $tierInfo,
                'next_tier' => $nextTier,
                'points_to_next' => $pointsToNext,
                'history' => $loyaltyHistory,
                'available_rewards' => $availableRewards,
                'redemptions' => $redemptionHistory,
                'enabled' => (bool) Setting::get('loyalty_enabled', false),
            ],
        ]);
    }

    public function orders()
    {
        return redirect()->route('tenant.account');
    }

    public function orderShow(string $orderNumber)
    {
        return redirect()->route('tenant.account');
    }

    public function downloads()
    {
        return redirect()->route('tenant.account');
    }

    public function update(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|max:30',
            'delivery_address' => 'nullable|string|max:500',
            'delivery_city' => 'nullable|string|max:100',
            'delivery_postal_code' => 'nullable|string|max:10',
            'date_of_birth' => 'nullable|date|before:today',
        ], [
            'date_of_birth.before' => 'Data urodzenia musi być datą w przeszłości.',
        ]);

        // If email changed, send verification link instead of updating immediately
        if ($validated['email'] !== $customer->email) {
            $token = Str::random(48);
            $customer->update([
                'pending_email' => $validated['email'],
                'email_change_token' => $token,
                'email_change_token_expires_at' => now()->addHours(24),
            ]);

            $verifyUrl = route('tenant.account.verify-email', ['token' => $token]);

            try {
                Mail::to($validated['email'])->queue(new EmailChangeMail($verifyUrl));
            } catch (\Exception $e) {
                Log::warning('Email change verification mail failed: ' . $e->getMessage());
            }

            unset($validated['email']);
        }

        if (array_key_exists('phone', $validated)) {
            $validated['phone'] = PhoneHelper::normalize($validated['phone']);
        }

        $customer->update($validated);

        Log::info('Account: dane zaktualizowane', [
            'customer_id' => $customer->id,
            'email_change' => isset($token),
            'fields' => array_keys($validated),
        ]);

        return back()->with('success', isset($token)
            ? 'Dane zaktualizowane. Sprawdź nowy adres e-mail i kliknij link weryfikacyjny.'
            : 'Dane zostały zaktualizowane.');
    }

    /**
     * Verify new email from the link sent in the email.
     */
    public function verifyEmail(Request $request, string $token)
    {
        $customer = Customer::where('email_change_token', $token)
            ->whereNotNull('pending_email')
            ->first();

        if (!$customer
            || ($customer->email_change_token_expires_at && $customer->email_change_token_expires_at->isPast())
        ) {
            // Clear stale token if expired
            if ($customer?->email_change_token_expires_at?->isPast()) {
                $customer->update([
                    'pending_email' => null,
                    'email_change_token' => null,
                    'email_change_token_expires_at' => null,
                ]);
            }

            return redirect()->route('tenant.account')
                ->withErrors(['error' => 'Nieprawidłowy lub wygasły link weryfikacyjny.']);
        }

        $customer->update([
            'email' => $customer->pending_email,
            'pending_email' => null,
            'email_change_token' => null,
            'email_change_token_expires_at' => null,
        ]);

        return redirect()->route('tenant.account')
            ->with('success', __('messages.email_changed'));
    }

    public function updatePassword(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $customer->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Obecne hasło jest nieprawidłowe.',
            ]);
        }

        $customer->update(['password' => Hash::make($validated['password'])]);

        // Must run after the DB already has the new hash, since this
        // re-verifies $validated['password'] against the current record.
        // Actual invalidation of other devices happens via EnsureCustomerAuth,
        // which compares each request's stored session password hash against
        // the current one and logs out any session that's now stale.
        Auth::guard('customer')->logoutOtherDevices($validated['password']);

        Log::info('Account: password changed', ['customer_id' => $customer->id]);

        return back()->with('success', __('messages.password_changed'));
    }

    public function cancelOrder(string $orderNumber)
    {
        $customer = auth('customer')->user();
        $order = Order::where('order_number', $orderNumber)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        if ($order->status !== 'pending') {
            return back()->withErrors(['cancel' => 'Zamówienie jest już w przygotowaniu i nie może zostać anulowane.']);
        }

        if ($order->created_at->diffInMinutes(now()) > 15) {
            return back()->withErrors(['cancel' => 'Czas na anulowanie zamówienia (15 minut) minął.']);
        }

        $order->update(['status' => 'cancelled']);
        Log::info('Account: order cancelled by the customer', ['order_number' => $order->order_number, 'customer_id' => $customer->id]);

        // Restore stock for tracked products (variant-aware — a variant's own
        // stock, not the parent product's, is what checkout actually decremented)
        $order->load('items');
        app(InventoryService::class)->releaseForOrder($order);

        return back()->with('success', __('messages.order_was_cancelled'));
    }

    public function destroy(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $request->validate(['password' => 'required']);

        if (!Hash::check($request->password, $customer->password)) {
            throw ValidationException::withMessages([
                'password' => 'Nieprawidłowe hasło.',
            ]);
        }

        // Anonymize orders instead of deleting (GDPR)
        Order::where('customer_id', $customer->id)->update([
            'customer_name' => 'Usunięty użytkownik',
            'customer_email' => null,
            'customer_id' => null,
        ]);

        Log::info('Account: account deleted (GDPR)', ['customer_id' => $customer->id, 'email' => $customer->email]);

        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $customer->delete();

        return redirect()->route('tenant.shop')
            ->with('success', __('messages.account_deleted'));
    }
}
