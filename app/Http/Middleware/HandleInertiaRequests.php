<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Tenant\Manager\RolePermissionsController;
use App\Models\Landlord\SupportTicket;
use App\Models\Tenant\Article;
use App\Models\Tenant\Collection;
use App\Models\Tenant\Promotion;
use App\Models\Tenant\RolePermission;
use App\Models\Tenant\Setting;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $this->getAuthUser(),
                'customer' => $this->getAuthCustomer(),
            ],
            // Read straight off the initial payload by the front end, before
            // the first component renders, to pick the dictionary to load.
            'locale' => fn () => app()->getLocale(),
            'availableLocales' => fn () => $this->availableLocales(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'contact_success' => fn () => $request->session()->get('contact_success'),
                'new_secret' => fn () => $request->session()->get('new_secret'),
                'recovery_codes' => fn () => $request->session()->get('recovery_codes'),
            ],
            'tenant' => fn () => $this->getTenantData(),
            'has_collections' => fn () => $this->hasCollections(),
            'has_blog' => fn () => $this->hasBlog(),
            'announcement' => fn () => $this->getAnnouncementData(),
            'active_promotion' => fn () => $this->getActivePromotion(),
            'impersonating' => fn () => $request->session()->get('impersonating', false),
            'app_version' => fn () => $this->getTenantVersion(),
            'app_name' => config('app.name'),
            'current_currency' => fn () => $this->getCurrentCurrency($request),
            'enabled_currencies' => fn () => $this->getEnabledCurrencies(),
            'currency_rate' => fn () => $this->getCurrencyRate($request),
            'current_locale' => fn () => app()->getLocale(),
            'available_languages' => fn () => $this->getAvailableLanguages(),
            'vapidPublicKey' => fn () => config('webpush.vapid.public_key', ''),
            'landlordUnreadSupportCount' => fn () => $this->getLandlordUnreadSupportCount(),
        ];
    }

    /**
     * Languages this shop offers, always including the one it is served in.
     *
     * A shop that has not been configured for more than one language gets a
     * single entry, and the switcher has nothing to switch between — which is
     * the point: an interface that offers a language it has no translation
     * for is worse than one that offers none.
     *
     * @return list<string>
     */
    protected function availableLocales(): array
    {
        try {
            $available = Setting::get('available_languages', ['pl']);
        } catch (\Throwable) {
            return [app()->getLocale()];
        }

        if (is_string($available)) {
            $available = json_decode($available, true) ?: ['pl'];
        }

        $available = array_values(array_intersect(
            (array) $available,
            config('app.supported_locales', ['pl', 'en']),
        ));

        return $available ?: [app()->getLocale()];
    }

    protected function getAuthUser(): ?array
    {
        if ($user = auth('tenant')->user()) {
            $permissions = $user->role === 'manager'
                ? array_keys(RolePermissionsController::PERMISSIONS)
                : RolePermission::permissionsFor($user->role);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'permissions' => $permissions,
            ];
        }

        if ($user = auth('super_admin')->user()) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'super_admin',
            ];
        }

        return null;
    }

    protected function getAuthCustomer(): ?array
    {
        if ($customer = auth('customer')->user()) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'avatar' => $customer->avatar,
            ];
        }

        return null;
    }

    protected function getTenantData(): ?array
    {
        try {
            if (!tenancy()->initialized || !tenancy()->tenant) {
                return null;
            }

            // Single query instead of ~39 individual Setting::get() calls
            $s = Setting::getAllAsArray();

            $bool = fn ($key, $default) => filter_var($s[$key] ?? $default, FILTER_VALIDATE_BOOLEAN);

            return [
                'id' => tenancy()->tenant?->id,
                'name' => $s['shop_name'] ?? config('app.name'),
                'phone' => $s['shop_phone'] ?? null,
                'email' => $s['shop_email'] ?? null,
                'address' => $s['shop_address'] ?? null,
                'description' => $s['shop_description'] ?? null,
                'currency' => $s['currency'] ?? 'PLN',
                'logo_url' => $s['logo_url'] ?? null,
                'favicon_url' => $s['favicon_url'] ?? null,
                'hero_image_url' => $s['hero_image_url'] ?? null,
                'hero_title' => $s['hero_title'] ?? null,
                'hero_subtitle' => $s['hero_subtitle'] ?? null,
                'about_enabled' => $bool('about_enabled', false),
                'about_title' => $s['about_title'] ?? 'O nas',
                'about_text' => $s['about_text'] ?? null,
                'about_image_url' => $s['about_image_url'] ?? null,
                'gallery_enabled' => $bool('gallery_enabled', false),
                'gallery_title' => $s['gallery_title'] ?? 'Galeria',
                'gallery_images' => isset($s['gallery_images']) ? (is_string($s['gallery_images']) ? json_decode($s['gallery_images'], true) : $s['gallery_images']) : null,
                'theme_primary_color' => $s['theme_primary_color'] ?? '#4f46e5',
                'theme_font' => $s['theme_font'] ?? 'inter',
                'custom_css' => $s['custom_css'] ?? '',
                'homepage_blocks' => isset($s['homepage_blocks']) ? (is_string($s['homepage_blocks']) ? json_decode($s['homepage_blocks'], true) : $s['homepage_blocks']) : null,
                'facebook_url' => $s['facebook_url'] ?? null,
                'instagram_url' => $s['instagram_url'] ?? null,
                'tiktok_url' => $s['tiktok_url'] ?? null,
                'google_analytics_id' => $s['google_analytics_id'] ?? null,
                'facebook_pixel_id' => $s['facebook_pixel_id'] ?? null,
                'tiktok_pixel_id' => $s['tiktok_pixel_id'] ?? null,
                'vacation_mode' => $bool('vacation_mode', false),
                'vacation_message' => $s['vacation_message'] ?? '',
                'orders_paused' => $bool('orders_paused', false),
                'free_shipping_threshold' => (float) ($s['free_shipping_threshold'] ?? 0),
                'ga4_measurement_id' => $s['ga4_measurement_id'] ?? $s['google_analytics_id'] ?? null,
                'urgency' => [
                    'countdown_enabled' => $bool('urgency_countdown_enabled', true),
                    'stock_enabled' => $bool('urgency_stock_enabled', true),
                    'stock_threshold' => (int) ($s['urgency_stock_threshold'] ?? 5),
                    'viewers_enabled' => $bool('urgency_viewers_enabled', false),
                    'viewers_min' => (int) ($s['urgency_viewers_min'] ?? 5),
                    'viewers_max' => (int) ($s['urgency_viewers_max'] ?? 24),
                    'sold_enabled' => $bool('urgency_sold_enabled', false),
                    'sold_min' => (int) ($s['urgency_sold_min'] ?? 12),
                    'sold_max' => (int) ($s['urgency_sold_max'] ?? 84),
                    'delivery_enabled' => $bool('urgency_delivery_enabled', false),
                    'delivery_cutoff' => $s['urgency_delivery_cutoff'] ?? '14:00',
                ],
                'payment_methods' => array_values(array_filter([
                    ($bool('payment_p24_enabled', false) || $bool('payment_payu_enabled', false) || $bool('payment_tpay_enabled', false)) ? 'visa' : null,
                    ($bool('payment_p24_enabled', false) || $bool('payment_payu_enabled', false) || $bool('payment_tpay_enabled', false)) ? 'mastercard' : null,
                    ($bool('payment_p24_enabled', false) || $bool('payment_payu_enabled', false) || $bool('payment_tpay_enabled', false)) ? 'blik' : null,
                    ($bool('payment_p24_enabled', false) || $bool('payment_payu_enabled', false) || $bool('payment_tpay_enabled', false)) ? 'przelewy' : null,
                    $bool('payment_bank_transfer_enabled', false) ? 'przelew' : null,
                    $bool('payment_cash_on_delivery_enabled', false) ? 'pobranie' : null,
                ])),
            ];
        } catch (\Exception $e) {
            return ['name' => config('app.name')];
        }
    }

    protected function getActivePromotion(): ?array
    {
        try {
            if (!tenancy()->initialized || !tenancy()->tenant) {
                return null;
            }

            $promo = Promotion::active()->first();

            if (!$promo) {
                return null;
            }

            return [
                'id' => $promo->id,
                'name' => $promo->name,
                'banner_text' => $promo->banner_text,
                'starts_at' => $promo->starts_at?->toIso8601String(),
                'ends_at' => $promo->ends_at?->toIso8601String(),
                'discount_code' => $promo->discount_code,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function getAnnouncementData(): ?array
    {
        try {
            if (!tenancy()->initialized || !tenancy()->tenant) {
                return null;
            }

            $s = Setting::getAllAsArray();
            $bool = fn ($key, $default) => filter_var($s[$key] ?? $default, FILTER_VALIDATE_BOOLEAN);

            if (!$bool('announcement_enabled', false)) {
                return null;
            }

            return [
                'enabled' => true,
                'text' => $s['announcement_text'] ?? '',
                'color' => $s['announcement_color'] ?? '#4F46E5',
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function hasCollections(): bool
    {
        try {
            if (!tenancy()->initialized || !tenancy()->tenant) {
                return false;
            }

            return Collection::active()->exists();
        } catch (\Exception) {
            return false;
        }
    }

    protected function hasBlog(): bool
    {
        try {
            if (!tenancy()->initialized || !tenancy()->tenant) {
                return false;
            }

            return Article::published()->exists();
        } catch (\Exception) {
            return false;
        }
    }

    protected function getCurrentCurrency(Request $request): string
    {
        return $request->session()->get('currency', config('shop.currency', 'PLN'));
    }

    /**
     * CurrencyService::convert()/formatAmount() (real NBP-rate integration)
     * had zero callers anywhere — the storefront always formatted prices as
     * hardcoded PLN regardless of current_currency, so switching currency
     * (the CurrencyController::set() endpoint already existed) had no
     * visible effect at all. This shares the PLN→current_currency rate so
     * Vue can convert without an extra request per price.
     */
    protected function getCurrencyRate(Request $request): float
    {
        $currency = $this->getCurrentCurrency($request);

        if ($currency === 'PLN') {
            return 1.0;
        }

        try {
            return app(CurrencyService::class)->getRate('PLN', $currency);
        } catch (\Exception) {
            return 1.0;
        }
    }

    protected function getEnabledCurrencies(): array
    {
        try {
            if (!tenancy()->initialized || !tenancy()->tenant) {
                return ['PLN'];
            }
            $raw = Setting::get('enabled_currencies', ['PLN']);
            if (is_string($raw)) {
                $raw = json_decode($raw, true) ?? ['PLN'];
            }

            return is_array($raw) ? $raw : ['PLN'];
        } catch (\Exception) {
            return ['PLN'];
        }
    }

    protected function getAvailableLanguages(): array
    {
        try {
            if (!tenancy()->initialized || !tenancy()->tenant) {
                return ['pl'];
            }
            $raw = Setting::get('available_languages', ['pl']);
            if (is_string($raw)) {
                $raw = json_decode($raw, true) ?? ['pl'];
            }

            return is_array($raw) ? $raw : ['pl'];
        } catch (\Exception) {
            return ['pl'];
        }
    }

    protected function getTenantVersion(): string
    {
        try {
            if (!tenancy()->initialized || !tenancy()->tenant) {
                return 'stable';
            }

            return tenancy()->tenant?->version ?? 'stable';
        } catch (\Exception $e) {
            return 'stable';
        }
    }

    protected function getLandlordUnreadSupportCount(): int
    {
        try {
            if (!auth('super_admin')->check()) {
                return 0;
            }

            return SupportTicket::where('unread_by_admin', true)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
