<?php

use App\Http\Controllers\Tenant\Auth\TwoFactorController;
use App\Http\Controllers\Tenant\AuthController;
use App\Http\Controllers\Tenant\Client\AbandonedCartController;
use App\Http\Controllers\Tenant\Client\AccountController;
use App\Http\Controllers\Tenant\Client\AddressAutocompleteController;
use App\Http\Controllers\Tenant\Client\ArticleController as ClientArticleController;
use App\Http\Controllers\Tenant\Client\BundleController;
use App\Http\Controllers\Tenant\Client\CartUpsellController;
use App\Http\Controllers\Tenant\Client\CategoryController as ClientCategoryController;
use App\Http\Controllers\Tenant\Client\ChatController;
use App\Http\Controllers\Tenant\Client\CheckoutController;
use App\Http\Controllers\Tenant\Client\CollectionController as ClientCollectionController;
use App\Http\Controllers\Tenant\Client\ContactController;
use App\Http\Controllers\Tenant\Client\CurrencyController;
use App\Http\Controllers\Tenant\Client\CustomerAuthController;
use App\Http\Controllers\Tenant\Client\DownloadController;
use App\Http\Controllers\Tenant\Client\GdprController;
use App\Http\Controllers\Tenant\Client\GiftCardController as ClientGiftCardController;
use App\Http\Controllers\Tenant\Client\InvoiceController;
use App\Http\Controllers\Tenant\Client\LocaleController;
use App\Http\Controllers\Tenant\Client\NewsletterController;
use App\Http\Controllers\Tenant\Client\OrderTrackingController;
use App\Http\Controllers\Tenant\Client\PageController;
use App\Http\Controllers\Tenant\Client\PaymentController;
use App\Http\Controllers\Tenant\Client\PickupPointController;
use App\Http\Controllers\Tenant\Client\ProductController as ClientProductController;
use App\Http\Controllers\Tenant\Client\ReviewController as ClientReviewController;
use App\Http\Controllers\Tenant\Client\RmaController as ClientRmaController;
use App\Http\Controllers\Tenant\Client\ShopController;
use App\Http\Controllers\Tenant\Client\SocialAuthController;
use App\Http\Controllers\Tenant\Client\TrustController;
use App\Http\Controllers\Tenant\Client\WishlistController;
use App\Http\Controllers\Tenant\Manager\AbandonedCartController as ManagerAbandonedCartController;
use App\Http\Controllers\Tenant\Manager\AiMediaController;
use App\Http\Controllers\Tenant\Manager\ArticleController as ManagerArticleController;
use App\Http\Controllers\Tenant\Manager\AuditLogController;
use App\Http\Controllers\Tenant\Manager\BackupController;
use App\Http\Controllers\Tenant\Manager\BadgeController;
use App\Http\Controllers\Tenant\Manager\BulkProductController;
use App\Http\Controllers\Tenant\Manager\CategoryController;
use App\Http\Controllers\Tenant\Manager\ChatManagerController;
use App\Http\Controllers\Tenant\Manager\CollectionController as ManagerCollectionController;
use App\Http\Controllers\Tenant\Manager\CustomerController;
use App\Http\Controllers\Tenant\Manager\CustomerImportController;
use App\Http\Controllers\Tenant\Manager\DashboardController;
use App\Http\Controllers\Tenant\Manager\DiscountCodeController;
use App\Http\Controllers\Tenant\Manager\FlashSaleController;
use App\Http\Controllers\Tenant\Manager\FraudController;
use App\Http\Controllers\Tenant\Manager\FulfillmentController;
use App\Http\Controllers\Tenant\Manager\GdprController as ManagerGdprController;
use App\Http\Controllers\Tenant\Manager\GiftCardController as ManagerGiftCardController;
use App\Http\Controllers\Tenant\Manager\HomepageBuilderController;
use App\Http\Controllers\Tenant\Manager\HubImpersonateController;
use App\Http\Controllers\Tenant\Manager\ImpersonateController;
use App\Http\Controllers\Tenant\Manager\InstallController;
use App\Http\Controllers\Tenant\Manager\InventoryController;
use App\Http\Controllers\Tenant\Manager\KbArticleController as ManagerKbArticleController;
use App\Http\Controllers\Tenant\Manager\LicenseController;
use App\Http\Controllers\Tenant\Manager\LoyaltyCampaignController;
use App\Http\Controllers\Tenant\Manager\LoyaltyController;
use App\Http\Controllers\Tenant\Manager\LoyaltyRewardController;
use App\Http\Controllers\Tenant\Manager\MarketingController;
use App\Http\Controllers\Tenant\Manager\NotificationController;
use App\Http\Controllers\Tenant\Manager\OrderImportController;
use App\Http\Controllers\Tenant\Manager\OrderManagementController;
use App\Http\Controllers\Tenant\Manager\PageBuilderController;
use App\Http\Controllers\Tenant\Manager\PickingListController;
use App\Http\Controllers\Tenant\Manager\ProductBundleController;
use App\Http\Controllers\Tenant\Manager\ProductController;
use App\Http\Controllers\Tenant\Manager\ProductImportController;
use App\Http\Controllers\Tenant\Manager\ProductReviewController;
use App\Http\Controllers\Tenant\Manager\PromotionController;
use App\Http\Controllers\Tenant\Manager\PurchaseOrderController;
use App\Http\Controllers\Tenant\Manager\RefundController;
use App\Http\Controllers\Tenant\Manager\ReportController;
use App\Http\Controllers\Tenant\Manager\RmaController as ManagerRmaController;
use App\Http\Controllers\Tenant\Manager\RolePermissionsController;
use App\Http\Controllers\Tenant\Manager\SeoCheckController;
use App\Http\Controllers\Tenant\Manager\SettingsController;
use App\Http\Controllers\Tenant\Manager\SetupController;
use App\Http\Controllers\Tenant\Manager\ShippingController;
use App\Http\Controllers\Tenant\Manager\StaffController;
use App\Http\Controllers\Tenant\Manager\StaffPerformanceController;
use App\Http\Controllers\Tenant\Manager\StaffReportsController;
use App\Http\Controllers\Tenant\Manager\SupplierController;
use App\Http\Controllers\Tenant\Manager\SupportController;
use App\Http\Controllers\Tenant\Manager\TaxController;
use App\Http\Controllers\Tenant\Manager\VolumeDiscountController;
use App\Http\Controllers\Tenant\Manager\WarehouseController;
use App\Http\Controllers\Tenant\Manager\WebhookController;
use App\Http\Controllers\Tenant\SitemapController;
use App\Http\Controllers\Tenant\Staff\FulfillmentStaffController;
use App\Http\Controllers\Tenant\Staff\KbArticleController as StaffKbArticleController;
use App\Http\Controllers\Tenant\Staff\PushSubscriptionController;
use App\Http\Controllers\Tenant\Staff\QuickControlsController;
use App\Http\Controllers\Tenant\Staff\StaffReportController;
use App\Http\Controllers\Tenant\Staff\WarehouseStaffController;
use App\Http\Middleware\Tenant\EnsureInstallComplete;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes (Shop Frontend & Backend)
|--------------------------------------------------------------------------
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    EnsureInstallComplete::class,
    'set.locale',
    'set.currency',
])->group(function () {

    // ─── Tenant storage file serving (replaces missing public/storage symlink) ──
    // Apache's .htaccess forwards non-existent files to index.php, so this route
    // serves tenant images at /storage/{path} directly from the tenant's disk.
    // Kept for compatibility with any legacy /storage/ paths from tenant disk
    Route::get('/storage/{path}', function (string $path) {
        $path = ltrim(str_replace(['..', "\0"], '', $path), '/');
        $disk = Storage::disk('public');
        $fullPath = $disk->path($path);
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            abort(404);
        }
        $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    })->where('path', '.*')->name('tenant.storage');

    // ─── Install Wizard (public, only when setup not done) ──────────
    // check.setup.pending blocks the entire group once setup_completed=true —
    // previously only some individual methods checked this, and saveShop()/
    // complete() didn't, letting anyone re-run those two against a live store.
    Route::middleware('check.setup.pending')->group(function () {
        Route::get('/install', [InstallController::class, 'index'])->name('tenant.install');
        Route::post('/install/account', [InstallController::class, 'createAccount'])->name('tenant.install.account')->middleware('throttle:5,60');
        Route::post('/install/shop', [InstallController::class, 'saveShop'])->name('tenant.install.shop')->middleware('throttle:10,60');
        Route::post('/install/branding', [InstallController::class, 'saveBranding'])->name('tenant.install.branding')->middleware('throttle:10,60');
        Route::post('/install/complete', [InstallController::class, 'complete'])->name('tenant.install.complete')->middleware('throttle:10,60');
    });

    // ─── 2FA Routes (public, post-login verification) ───────────────
    Route::get('/staff/2fa/verify', [TwoFactorController::class, 'showVerify'])->name('tenant.2fa.verify');
    Route::post('/staff/2fa/verify', [TwoFactorController::class, 'verify'])->name('tenant.2fa.verify.post')->middleware('throttle:2fa-verify');
    Route::post('/staff/2fa/send-code', [TwoFactorController::class, 'sendCode'])->name('tenant.2fa.send-code')->middleware('throttle:5,60,2fa-code');

    // ─── Staff Authentication ───────────────────────────────────────
    Route::get('/login', [AuthController::class, 'showLogin'])->name('tenant.login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,60,staff-login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('tenant.logout');
    Route::get('/hub-impersonate/{token}', [HubImpersonateController::class, 'handle'])->name('tenant.hub-impersonate');

    // Staff Password Reset
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('tenant.password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('tenant.password.email')->middleware('throttle:5,60,staff-reset');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('tenant.password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('tenant.password.update')->middleware('throttle:10,60,staff-reset-consume');

    // ─── Currency switcher ──────────────────────────────────────────
    Route::post('/currency', [CurrencyController::class, 'set'])->name('tenant.currency.set');
    Route::post('/locale', [LocaleController::class, 'set'])->name('tenant.locale.set');

    // ─── Customer Authentication ────────────────────────────────────
    Route::name('tenant.client.')->prefix('konto')->group(function () {
        Route::get('/logowanie', [CustomerAuthController::class, 'showLogin'])->name('login');
        Route::post('/logowanie', [CustomerAuthController::class, 'login'])->middleware('throttle:10,60,customer-login');
        Route::get('/rejestracja', [CustomerAuthController::class, 'showRegister'])->name('register');
        Route::post('/rejestracja', [CustomerAuthController::class, 'register'])->middleware('throttle:10,60,customer-register');
        Route::post('/wyloguj', [CustomerAuthController::class, 'logout'])->name('logout');

        // Social Auth
        Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])->name('social.redirect');
        Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

        // Customer Password Reset
        Route::get('/reset-hasla', [CustomerAuthController::class, 'showForgotPassword'])->name('password.request');
        Route::post('/reset-hasla', [CustomerAuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:5,60,customer-reset');
        Route::get('/reset-hasla/{token}', [CustomerAuthController::class, 'showResetPassword'])->name('password.reset');
        Route::post('/nowe-haslo', [CustomerAuthController::class, 'resetPassword'])->name('password.update')->middleware('throttle:10,60,customer-reset-consume');
    });

    // ─── Client-facing routes (public) ──────────────────────────────
    Route::middleware(['check.license'])->name('tenant.')->group(function () {
        // Shop home
        Route::get('/', [ShopController::class, 'index'])->name('shop');
        Route::get('/produkty', [ShopController::class, 'allProducts'])->name('shop.products');
        Route::get('/szukaj', [ShopController::class, 'search'])->name('shop.search');
        Route::get('/api/szukaj-podpowiedzi', [ShopController::class, 'searchSuggest'])->name('api.search-suggest')->middleware('throttle:60,1');

        // Categories
        Route::get('/kategoria/{category:slug}', [ClientCategoryController::class, 'show'])->name('category.show');

        // Products
        Route::get('/produkt/{product:slug}', [ClientProductController::class, 'show'])->name('product.show');
        Route::get('/zestaw/{bundle:slug}', [BundleController::class, 'show'])->name('bundle.show');

        // Checkout
        Route::get('/koszyk', [CheckoutController::class, 'cart'])->name('cart');
        Route::get('/kasa', [CheckoutController::class, 'index'])->name('checkout');
        Route::post('/kasa', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:5,60,checkout');
        Route::post('/kasa/shipping-methods', [CheckoutController::class, 'calculateShipping'])->name('checkout.shipping-methods');
        Route::get('/kasa/pickup-points', [PickupPointController::class, 'index'])->name('checkout.pickup-points')->middleware('throttle:60,1');
        Route::post('/kasa/validate-discount', [CheckoutController::class, 'validateDiscountCode'])->name('checkout.validate-discount')->middleware('throttle:15,60');
        Route::post('/kasa/gift-card', [ClientGiftCardController::class, 'check'])->name('checkout.gift-card')->middleware('throttle:15,60');

        // Payments — bound on order_number (not the raw auto-increment id) and
        // verified against tracking_token/owner/staff in PaymentController,
        // same pattern as order.tracking below. Without this, any visitor could
        // enumerate /platnosc/{id}/inicjuj and get redirected into a live
        // Przelewy24 session showing another customer's name/email/order total.
        Route::get('/platnosc/{orderNumber}/inicjuj', [PaymentController::class, 'initiate'])->name('payment.initiate');
        Route::get('/platnosc/{orderNumber}/powrot', [PaymentController::class, 'return'])->name('payment.return');

        // Payment Webhooks (CSRF excluded in bootstrap/app.php)
        Route::post('/payment/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');
        Route::post('/payment/webhook/payu', [PaymentController::class, 'webhookPayU'])->name('payment.webhook.payu');
        Route::post('/payment/webhook/tpay', [PaymentController::class, 'webhookTpay'])->name('payment.webhook.tpay');

        // Order Tracking (by token, no login required)
        Route::get('/zamowienie/{token}/sledzenie', [OrderTrackingController::class, 'show'])->name('order.tracking')->middleware('throttle:30,1');

        // Digital Downloads (token-based, no login required)
        Route::get('/pobieranie/{token}', [DownloadController::class, 'download'])->name('download')->middleware('throttle:30,1');

        // Static pages
        Route::get('/regulamin', [PageController::class, 'terms'])->name('terms');
        Route::get('/polityka-prywatnosci', [PageController::class, 'privacy'])->name('privacy');
        Route::get('/dostawa', [PageController::class, 'shipping'])->name('shipping');
        Route::get('/zwroty', [PageController::class, 'returns'])->name('returns');
        Route::get('/faq', [PageController::class, 'faq'])->name('faq');
        Route::get('/kontakt', [ContactController::class, 'index'])->name('contact');
        Route::post('/kontakt', [ContactController::class, 'send'])->name('contact.send')->middleware('throttle:5,60,contact');
        Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe')->middleware('throttle:5,60,newsletter');

        // SEO
        Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
        Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

        // Blog / CMS (public)
        Route::get('/blog', [ClientArticleController::class, 'index'])->name('blog.index');
        Route::get('/blog/{slug}', [ClientArticleController::class, 'show'])->name('blog.show');

        // Custom pages (Page Builder)
        Route::get('/strona/{slug}', [PageController::class, 'show'])->name('page.show');

        // Reviews / Trust
        Route::get('/opinie', [TrustController::class, 'index'])->name('trust.index');

        // Product related endpoint (AJAX)
        Route::get('/produkt/{product:slug}/related', [ClientProductController::class, 'related'])->name('product.related');

        // Abandoned cart tracking (API)
        Route::post('/api/cart/track', [AbandonedCartController::class, 'track'])->name('cart.track')->middleware('throttle:30,60');
        Route::post('/api/cart/convert', [AbandonedCartController::class, 'convert'])->name('cart.convert')->middleware('throttle:30,60');

        // Cart cross-sell suggestions (API)
        Route::get('/api/cart/upsell', [CartUpsellController::class, 'suggestions'])->name('cart.upsell')->middleware('throttle:60,60');

        // Marketing unsubscribe (public)
        Route::get('/marketing/unsubscribe', [MarketingController::class, 'unsubscribe'])->name('marketing.unsubscribe');

        // Collections (public)
        Route::get('/kolekcje', [ClientCollectionController::class, 'index'])->name('collections.index');
        Route::get('/kolekcje/{slug}', [ClientCollectionController::class, 'show'])->name('collections.show');

        // Address autocomplete (public API, rate limited)
        Route::get('/api/address-suggest', [AddressAutocompleteController::class, 'suggest'])->name('api.address-suggest')->middleware('throttle:30,1');

        // Live Chat (public — guest can start chat)
        Route::post('/chat/start', [ChatController::class, 'start'])->name('chat.start')->middleware('throttle:10,60');
        Route::post('/chat/{conversation}/send', [ChatController::class, 'send'])->name('chat.send')->middleware('throttle:30,60');
        Route::get('/chat/{conversation}/poll', [ChatController::class, 'poll'])->name('chat.poll')->middleware('throttle:30,60');

        // Email verification
        Route::get('/moje-konto/verify-email/{token}', [AccountController::class, 'verifyEmail'])->name('account.verify-email');

        // RMA — Return requests. Public route: authorization is done inside the
        // controller (logged-in owner OR a valid order tracking_token), so guest
        // checkouts — the majority of traffic from anonymous TikTok ads — can
        // request a return too, not just logged-in customers.
        Route::get('/zamowienia/{orderNumber}/zwrot', [ClientRmaController::class, 'create'])->name('rma.create');
        Route::post('/zamowienia/{orderNumber}/zwrot', [ClientRmaController::class, 'store'])->name('rma.store')->middleware('throttle:10,60,rma-store');

        // ─── Customer authenticated routes ───────────────────────────
        Route::middleware(['auth.customer'])->group(function () {
            // Account
            Route::get('/moje-konto', [AccountController::class, 'index'])->name('account');
            Route::put('/moje-konto', [AccountController::class, 'update'])->name('account.update');
            Route::put('/moje-konto/haslo', [AccountController::class, 'updatePassword'])->name('account.password');
            Route::delete('/moje-konto', [AccountController::class, 'destroy'])->name('account.destroy');

            // Orders history
            Route::get('/moje-konto/zamowienia', [AccountController::class, 'orders'])->name('account.orders');
            Route::get('/moje-konto/zamowienia/{orderNumber}', [AccountController::class, 'orderShow'])->name('account.order.show');
            Route::delete('/moje-konto/zamowienia/{orderNumber}/anuluj', [AccountController::class, 'cancelOrder'])->name('account.order.cancel');
            Route::get('/zamowienia/{orderNumber}/faktura', [InvoiceController::class, 'show'])->name('order.invoice');

            // Downloads (my digital files)
            Route::get('/moje-konto/pobrane', [AccountController::class, 'downloads'])->name('account.downloads');

            // Wishlist
            Route::get('/lista-zyczen', [WishlistController::class, 'index'])->name('wishlist');
            Route::post('/lista-zyczen/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

            // Reviews
            Route::post('/recenzja', [ClientReviewController::class, 'store'])->name('review.store')->middleware('throttle:10,60,review-store');

            // GDPR (requires customer auth)
            Route::get('/moje-konto/gdpr/export', [GdprController::class, 'export'])->name('gdpr.export');
            Route::post('/moje-konto/gdpr/delete-request', [GdprController::class, 'deleteRequest'])->name('gdpr.delete-request');
            Route::post('/moje-konto/gdpr/anonymize', [GdprController::class, 'anonymize'])->name('gdpr.anonymize');
        });
    });

    // ─── Authenticated staff routes ─────────────────────────────────
    Route::get('/manager/impersonate', [ImpersonateController::class, 'handle'])->name('tenant.manager.impersonate');

    Route::middleware(['auth.tenant', '2fa'])->group(function () {

        Route::post('/broadcasting/auth', function (Request $request) {
            $request->setUserResolver(function () {
                return Auth::guard('tenant')->user();
            });

            return Broadcast::auth($request);
        });

        Route::get('/staff', [AuthController::class, 'redirectByRole'])->name('tenant.staff.redirect');

        // ─── Manager Panel ───────────────────────────────────────────
        Route::prefix('manager')->name('tenant.manager.')->middleware(['role:manager', 'check.setup'])->group(function () {
            Route::post('/impersonate/stop', [ImpersonateController::class, 'stop'])->name('impersonate.stop');
            Route::get('/setup', [SetupController::class, 'index'])->name('setup');
            Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');

            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

            // Products (bulk & import — before CRUD to avoid route conflicts)
            Route::patch('/products/bulk', [BulkProductController::class, 'update'])->name('products.bulk.update');
            Route::delete('/products/bulk', [BulkProductController::class, 'destroy'])->name('products.bulk.destroy');
            Route::post('/products/import', [ProductImportController::class, 'import'])->name('products.import');
            Route::post('/products/generate-description', [ProductImportController::class, 'generateDescription'])->name('products.generate-description');

            // Products
            Route::prefix('products')->name('products.')->group(function () {
                Route::get('/', [ProductController::class, 'index'])->name('index');
                Route::get('/create', [ProductController::class, 'create'])->name('create');
                Route::post('/', [ProductController::class, 'store'])->name('store');
                Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
                Route::put('/{product}', [ProductController::class, 'update'])->name('update');
                Route::patch('/{product}', [ProductController::class, 'update']);
                Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
                Route::patch('/{product}/toggle-publish', [ProductController::class, 'togglePublish'])->name('toggle-publish');
                Route::post('/{product}/images', [ProductController::class, 'uploadImage'])->name('images.upload');
                Route::delete('/{product}/images/{image}', [ProductController::class, 'destroyImage'])->name('images.destroy');
                Route::patch('/{product}/images/reorder', [ProductController::class, 'reorderImages'])->name('images.reorder');
                Route::post('/{product}/files', [ProductController::class, 'uploadFile'])->name('files.upload');
                Route::delete('/{product}/files/{file}', [ProductController::class, 'destroyFile'])->name('files.destroy');
                // Variants
                Route::get('/{product}/variants', [ProductController::class, 'variants'])->name('variants');
                Route::post('/{product}/variants', [ProductController::class, 'storeVariant'])->name('variants.store');
                Route::put('/{product}/variants/{variant}', [ProductController::class, 'updateVariant'])->name('variants.update');
                Route::delete('/{product}/variants/{variant}', [ProductController::class, 'destroyVariant'])->name('variants.destroy');
            });

            // Categories
            Route::prefix('categories')->name('categories.')->group(function () {
                Route::get('/', [CategoryController::class, 'index'])->name('index');
                Route::post('/', [CategoryController::class, 'store'])->name('store');
                Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
                Route::patch('/{category}', [CategoryController::class, 'update']);
                Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
                Route::patch('/reorder', [CategoryController::class, 'reorder'])->name('reorder');
            });

            // Product Attributes (global)
            Route::prefix('attributes')->name('attributes.')->group(function () {
                Route::get('/', [ProductController::class, 'attributes'])->name('index');
                Route::post('/', [ProductController::class, 'storeAttribute'])->name('store');
                Route::put('/{attribute}', [ProductController::class, 'updateAttribute'])->name('update');
                Route::delete('/{attribute}', [ProductController::class, 'destroyAttribute'])->name('destroy');
                Route::post('/{attribute}/values', [ProductController::class, 'storeAttributeValue'])->name('values.store');
                Route::delete('/{attribute}/values/{value}', [ProductController::class, 'destroyAttributeValue'])->name('values.destroy');
            });

            // Orders
            Route::prefix('orders')->name('orders.')->group(function () {
                Route::get('/', [OrderManagementController::class, 'index'])->name('index');
                Route::get('/manual/products', [OrderManagementController::class, 'manualProducts'])->name('manual.products')->middleware('throttle:60,1');
                Route::post('/manual', [OrderManagementController::class, 'manualStore'])->name('manual.store');
                Route::get('/{order}', [OrderManagementController::class, 'show'])->name('show');
                Route::patch('/{order}/status', [OrderManagementController::class, 'updateStatus'])->name('update-status');
                Route::patch('/{order}/payment-status', [OrderManagementController::class, 'updatePaymentStatus'])->name('update-payment-status');
                Route::patch('/{order}/fulfillment', [FulfillmentController::class, 'update'])->name('fulfillment.update');
                Route::post('/{order}/tracking', [FulfillmentController::class, 'addTracking'])->name('tracking.add');
                Route::post('/{order}/label', [FulfillmentController::class, 'createLabel'])->name('label.create');
                Route::post('/{order}/regenerate-downloads', [FulfillmentController::class, 'regenerateDownloads'])->name('downloads.regenerate');
                Route::get('/{orderNumber}/faktura', [InvoiceController::class, 'showForManager'])->name('invoice');
            });

            // Shipping Methods & Zones
            Route::prefix('shipping')->name('shipping.')->group(function () {
                Route::get('/', [ShippingController::class, 'index'])->name('index');
                Route::post('/methods', [ShippingController::class, 'storeMethod'])->name('methods.store');
                Route::put('/methods/{method}', [ShippingController::class, 'updateMethod'])->name('methods.update');
                Route::delete('/methods/{method}', [ShippingController::class, 'destroyMethod'])->name('methods.destroy');
                Route::patch('/methods/{method}/toggle', [ShippingController::class, 'toggleMethod'])->name('methods.toggle');
                Route::get('/zones', [ShippingController::class, 'zones'])->name('zones.index');
                Route::post('/zones', [ShippingController::class, 'storeZone'])->name('zones.store');
                Route::put('/zones/{zone}', [ShippingController::class, 'updateZone'])->name('zones.update');
                Route::delete('/zones/{zone}', [ShippingController::class, 'destroyZone'])->name('zones.destroy');
            });

            // Inventory
            Route::prefix('inventory')->name('inventory.')->group(function () {
                Route::get('/', [InventoryController::class, 'index'])->name('index');
                Route::patch('/{product}/adjust', [InventoryController::class, 'adjust'])->name('adjust');
                Route::get('/{product}/movements', [InventoryController::class, 'movements'])->name('movements');
                Route::get('/export', [InventoryController::class, 'export'])->name('export');
            });

            // Product Reviews (moderation)
            Route::prefix('reviews')->name('reviews.')->group(function () {
                Route::get('/', [ProductReviewController::class, 'index'])->name('index');
                Route::patch('/{review}/approve', [ProductReviewController::class, 'approve'])->name('approve');
                Route::patch('/{review}/reject', [ProductReviewController::class, 'reject'])->name('reject');
                Route::post('/{review}/reply', [ProductReviewController::class, 'reply'])->name('reply');
                Route::delete('/{review}', [ProductReviewController::class, 'destroy'])->name('destroy');
            });

            // Refunds
            Route::prefix('refunds')->name('refunds.')->group(function () {
                Route::get('/', [RefundController::class, 'index'])->name('index');
                Route::get('/{refund}', [RefundController::class, 'show'])->name('show');
                Route::patch('/{refund}/approve', [RefundController::class, 'approve'])->name('approve')->middleware('permission:process_refunds');
                Route::patch('/{refund}/reject', [RefundController::class, 'reject'])->name('reject')->middleware('permission:process_refunds');
                Route::post('/{refund}/process', [RefundController::class, 'process'])->name('process')->middleware('permission:process_refunds');
            });

            // Tax Rates
            Route::prefix('tax')->name('tax.')->group(function () {
                Route::get('/', [TaxController::class, 'index'])->name('index');
                Route::post('/', [TaxController::class, 'store'])->name('store');
                Route::post('/oss', [TaxController::class, 'storeOss'])->name('oss.store');
                Route::put('/{taxRate}', [TaxController::class, 'update'])->name('update');
                Route::delete('/{taxRate}', [TaxController::class, 'destroy'])->name('destroy');
            });

            // Discount Codes
            Route::prefix('discounts')->name('discounts.')->group(function () {
                Route::get('/', [DiscountCodeController::class, 'index'])->name('index');
                Route::post('/', [DiscountCodeController::class, 'store'])->name('store');
                Route::put('/{discountCode}', [DiscountCodeController::class, 'update'])->name('update');
                Route::delete('/{discountCode}', [DiscountCodeController::class, 'destroy'])->name('destroy');
                Route::post('/{discountCode}/toggle', [DiscountCodeController::class, 'toggle'])->name('toggle');
            });

            // Reports
            Route::prefix('reports')->name('reports.')->middleware('plan.feature:analytics')->group(function () {
                Route::get('/', [ReportController::class, 'index'])->name('index');
                Route::get('/export-csv', [ReportController::class, 'exportCsv'])->name('export-csv');
                Route::get('/products', [ReportController::class, 'productPerformance'])->name('products');
                Route::get('/profit', [ReportController::class, 'profitAnalysis'])->name('profit');
                Route::get('/retention', [ReportController::class, 'retentionAnalysis'])->name('retention');
                Route::get('/acquisition', [ReportController::class, 'customerAcquisition'])->name('acquisition');
            });

            // Customers
            Route::prefix('customers')->name('customers.')->group(function () {
                Route::get('/', [CustomerController::class, 'index'])->name('index');
                Route::get('/export', [CustomerController::class, 'export'])->name('export');
                Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
            });

            // Loyalty Program
            Route::prefix('loyalty')->name('loyalty.')->group(function () {
                Route::get('/', [LoyaltyController::class, 'index'])->name('index');
                Route::post('/customers/{customer}/points', [LoyaltyController::class, 'addPoints'])->name('add-points');
                Route::get('/customers/{customer}', [LoyaltyController::class, 'customerDetail'])->name('customer-detail');

                Route::prefix('rewards')->name('rewards.')->group(function () {
                    Route::get('/', [LoyaltyRewardController::class, 'index'])->name('index');
                    Route::post('/', [LoyaltyRewardController::class, 'store'])->name('store');
                    Route::put('/{reward}', [LoyaltyRewardController::class, 'update'])->name('update');
                    Route::delete('/{reward}', [LoyaltyRewardController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('campaigns')->name('campaigns.')->group(function () {
                    Route::get('/', [LoyaltyCampaignController::class, 'index'])->name('index');
                    Route::post('/', [LoyaltyCampaignController::class, 'store'])->name('store');
                    Route::put('/{campaign}', [LoyaltyCampaignController::class, 'update'])->name('update');
                    Route::delete('/{campaign}', [LoyaltyCampaignController::class, 'destroy'])->name('destroy');
                });
            });

            // Marketing / Email Campaigns
            Route::prefix('marketing')->name('marketing.')->group(function () {
                Route::get('/', [MarketingController::class, 'index'])->name('index');
                Route::post('/', [MarketingController::class, 'store'])->name('store');
                Route::put('/{campaign}', [MarketingController::class, 'update'])->name('update');
                Route::post('/{campaign}/send', [MarketingController::class, 'send'])->name('send')->middleware('throttle:3,60');
                Route::delete('/{campaign}', [MarketingController::class, 'destroy'])->name('destroy');
            });

            // AI Media — banery i video reklamowe
            Route::prefix('ai-media')->name('ai-media.')->group(function () {
                Route::get('/banery', [AiMediaController::class, 'bannerPage'])->name('banners');
                Route::post('/banner/preview', [AiMediaController::class, 'previewBanner'])->name('banner.preview')->middleware('throttle:30,1');
                Route::post('/banner/save', [AiMediaController::class, 'saveBanner'])->name('banner.save')->middleware('throttle:20,1');
                Route::get('/banner/lista', [AiMediaController::class, 'listBanners'])->name('banner.list');
                Route::delete('/banner/usun', [AiMediaController::class, 'deleteBanner'])->name('banner.delete');
                Route::post('/video/generuj', [AiMediaController::class, 'generateVideo'])->name('video.generate')->middleware('throttle:10,1');
                Route::get('/video/status', [AiMediaController::class, 'videoStatus'])->name('video.status');
            });

            // Staff Management (CRUD)
            Route::prefix('staff')->name('staff.')->group(function () {
                Route::get('/', [StaffController::class, 'index'])->name('index');
                Route::get('/create', [StaffController::class, 'create'])->name('create');
                Route::post('/', [StaffController::class, 'store'])->name('store');
                Route::get('/{staff}/edit', [StaffController::class, 'edit'])->name('edit');
                Route::put('/{staff}', [StaffController::class, 'update'])->name('update');
                Route::delete('/{staff}', [StaffController::class, 'destroy'])->name('destroy');
            });

            // Staff Reports (manager view)
            Route::prefix('staff-reports')->name('staff-reports.')->group(function () {
                Route::get('/', [StaffReportsController::class, 'index'])->name('index');
                Route::patch('/{report}/mark-read', [StaffReportsController::class, 'markRead'])->name('mark-read');
                Route::post('/mark-all-read', [StaffReportsController::class, 'markAllRead'])->name('mark-all-read');
                Route::get('/export', [StaffReportsController::class, 'export'])->name('export');
            });

            // Settings
            Route::prefix('settings')->name('settings.')->group(function () {
                Route::get('/', [SettingsController::class, 'index'])->name('index');
                Route::put('/', [SettingsController::class, 'update'])->name('update');
                Route::post('/upload', [SettingsController::class, 'upload'])->name('upload');
                Route::post('/test-smtp', [SettingsController::class, 'testSmtp'])->name('test-smtp');
            });

            // Role Permissions
            Route::prefix('role-permissions')->name('role-permissions.')->group(function () {
                Route::get('/', [RolePermissionsController::class, 'index'])->name('index');
                Route::put('/', [RolePermissionsController::class, 'update'])->name('update');
            });

            // Gift Cards
            Route::prefix('gift-cards')->name('gift-cards.')->group(function () {
                Route::get('/', [ManagerGiftCardController::class, 'index'])->name('index');
                Route::post('/', [ManagerGiftCardController::class, 'store'])->name('store');
                Route::get('/{giftCard}', [ManagerGiftCardController::class, 'show'])->name('show');
                Route::delete('/{giftCard}', [ManagerGiftCardController::class, 'destroy'])->name('destroy');
                Route::patch('/{giftCard}/toggle', [ManagerGiftCardController::class, 'toggle'])->name('toggle');
            });

            // Product Bundles
            Route::prefix('bundles')->name('bundles.')->group(function () {
                Route::get('/', [ProductBundleController::class, 'index'])->name('index');
                Route::post('/', [ProductBundleController::class, 'store'])->name('store');
                Route::put('/{productBundle}', [ProductBundleController::class, 'update'])->name('update');
                Route::delete('/{productBundle}', [ProductBundleController::class, 'destroy'])->name('destroy');
            });

            // Articles / Blog
            Route::prefix('articles')->name('articles.')->group(function () {
                Route::get('/', [ManagerArticleController::class, 'index'])->name('index');
                Route::get('/create', [ManagerArticleController::class, 'create'])->name('create');
                Route::post('/', [ManagerArticleController::class, 'store'])->name('store');
                Route::get('/{article}/edit', [ManagerArticleController::class, 'edit'])->name('edit');
                Route::put('/{article}', [ManagerArticleController::class, 'update'])->name('update');
                Route::delete('/{article}', [ManagerArticleController::class, 'destroy'])->name('destroy');
                Route::patch('/{article}/toggle-status', [ManagerArticleController::class, 'toggleStatus'])->name('toggle-status');
            });

            // Audit Log
            Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
            Route::get('/audit-log/export', [AuditLogController::class, 'export'])->name('audit-log.export');

            // Homepage Builder
            Route::get('/homepage-builder', [HomepageBuilderController::class, 'index'])->name('homepage-builder.index');
            Route::post('/homepage-builder', [HomepageBuilderController::class, 'save'])->name('homepage-builder.save');

            // Page Builder
            Route::prefix('page-builder')->name('page-builder.')->group(function () {
                Route::get('/', [PageBuilderController::class, 'index'])->name('index');
                Route::get('/create', [PageBuilderController::class, 'create'])->name('create');
                Route::post('/', [PageBuilderController::class, 'store'])->name('store');
                Route::get('/{page}/edit', [PageBuilderController::class, 'edit'])->name('edit');
                Route::put('/{page}', [PageBuilderController::class, 'update'])->name('update');
                Route::delete('/{page}', [PageBuilderController::class, 'destroy'])->name('destroy');
            });

            // GDPR
            Route::get('/gdpr', [ManagerGdprController::class, 'requests'])->name('gdpr.requests');
            Route::post('/gdpr/{customer}/anonymize', [ManagerGdprController::class, 'anonymize'])->name('gdpr.anonymize');

            // Collections
            Route::prefix('collections')->name('collections.')->group(function () {
                Route::get('/', [ManagerCollectionController::class, 'index'])->name('index');
                Route::post('/', [ManagerCollectionController::class, 'store'])->name('store');
                Route::put('/{collection}', [ManagerCollectionController::class, 'update'])->name('update');
                Route::delete('/{collection}', [ManagerCollectionController::class, 'destroy'])->name('destroy');
                Route::post('/{collection}/products', [ManagerCollectionController::class, 'addProduct'])->name('products.add');
                Route::delete('/{collection}/products', [ManagerCollectionController::class, 'removeProduct'])->name('products.remove');
                Route::patch('/{collection}/reorder', [ManagerCollectionController::class, 'reorder'])->name('reorder');
            });

            // Flash Sales
            Route::prefix('flash-sales')->name('flash-sales.')->group(function () {
                Route::get('/', [FlashSaleController::class, 'index'])->name('index');
                Route::post('/', [FlashSaleController::class, 'store'])->name('store');
                Route::put('/{flashSale}', [FlashSaleController::class, 'update'])->name('update');
                Route::delete('/{flashSale}', [FlashSaleController::class, 'destroy'])->name('destroy');
                Route::post('/{flashSale}/products', [FlashSaleController::class, 'addProducts'])->name('products.add');
                Route::delete('/{flashSale}/products', [FlashSaleController::class, 'removeProduct'])->name('products.remove');
                Route::patch('/{flashSale}/products', [FlashSaleController::class, 'syncProducts'])->name('products');
            });

            // Volume Discounts
            Route::prefix('volume-discounts')->name('volume-discounts.')->group(function () {
                Route::get('/', [VolumeDiscountController::class, 'index'])->name('index');
                Route::post('/', [VolumeDiscountController::class, 'store'])->name('store');
                Route::put('/{volumeDiscount}', [VolumeDiscountController::class, 'update'])->name('update');
                Route::delete('/{volumeDiscount}', [VolumeDiscountController::class, 'destroy'])->name('destroy');
            });

            // Warehouses
            Route::prefix('warehouses')->name('warehouses.')->group(function () {
                Route::get('/', [WarehouseController::class, 'index'])->name('index');
                Route::post('/', [WarehouseController::class, 'store'])->name('store');
                Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('update');
                Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->name('destroy');
                Route::get('/{warehouse}/stock', [WarehouseController::class, 'stock'])->name('stock');
                Route::post('/{warehouse}/stock/adjust', [WarehouseController::class, 'adjustStock'])->name('stock.adjust');
                Route::post('/{warehouse}/stock/transfer', [WarehouseController::class, 'transferStock'])->name('stock.transfer');
            });

            // RMA
            Route::prefix('rma')->name('rma.')->group(function () {
                Route::get('/', [ManagerRmaController::class, 'index'])->name('index');
                Route::get('/{rma}', [ManagerRmaController::class, 'show'])->name('show');
                Route::patch('/{rma}', [ManagerRmaController::class, 'update'])->name('update');
            });

            // Suppliers
            Route::prefix('suppliers')->name('suppliers.')->group(function () {
                Route::get('/', [SupplierController::class, 'index'])->name('index');
                Route::post('/', [SupplierController::class, 'store'])->name('store');
                Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
                Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
            });

            // Purchase Orders
            Route::prefix('purchase-orders')->name('purchase-orders.')->group(function () {
                Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
                Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create');
                Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
                Route::get('/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('show');
                Route::put('/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('update');
                Route::post('/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('receive');
                Route::delete('/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('destroy');
            });

            // Staff Performance
            Route::get('/staff/performance', [StaffPerformanceController::class, 'index'])->name('staff.performance');

            // Knowledge Base (manager)
            Route::prefix('knowledge-base')->name('knowledge-base.')->group(function () {
                Route::get('/', [ManagerKbArticleController::class, 'index'])->name('index');
                Route::post('/', [ManagerKbArticleController::class, 'store'])->name('store');
                Route::put('/{kbArticle}', [ManagerKbArticleController::class, 'update'])->name('update');
                Route::delete('/{kbArticle}', [ManagerKbArticleController::class, 'destroy'])->name('destroy');
            });

            // Webhooks
            Route::prefix('webhooks')->name('webhooks.')->group(function () {
                Route::get('/', [WebhookController::class, 'index'])->name('index');
                Route::post('/', [WebhookController::class, 'store'])->name('store');
                Route::put('/{webhook}', [WebhookController::class, 'update'])->name('update');
                Route::delete('/{webhook}', [WebhookController::class, 'destroy'])->name('destroy');
                Route::post('/{webhook}/test', [WebhookController::class, 'test'])->name('test');
                Route::post('/{webhook}/regenerate-secret', [WebhookController::class, 'regenerateSecret'])->name('regenerate-secret');
            });

            // Order Import (CSV)
            Route::post('/orders/import', [OrderImportController::class, 'import'])->name('orders.import');

            // Customer Import (CSV)
            Route::post('/customers/import', [CustomerImportController::class, 'import'])->name('customers.import');

            // SEO Check
            Route::get('/products/{product}/seo-check', [SeoCheckController::class, 'check'])->name('products.seo-check');

            // Hub pages
            Route::get('/catalog', fn () => Inertia::render('Tenant/Manager/Catalog/Index'))->name('catalog.index');
            Route::get('/sales', fn () => Inertia::render('Tenant/Manager/Sales/Index'))->name('sales.index');
            Route::get('/supply', fn () => Inertia::render('Tenant/Manager/Supply/Index'))->name('supply.index');
            Route::get('/tools', fn () => Inertia::render('Tenant/Manager/Tools/Index'))->name('tools.index');

            // Promotions
            Route::prefix('promotions')->name('promotions.')->group(function () {
                Route::get('/', [PromotionController::class, 'index'])->name('index');
                Route::post('/', [PromotionController::class, 'store'])->name('store');
                Route::put('/{promotion}', [PromotionController::class, 'update'])->name('update');
                Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->name('destroy');
                Route::post('/banner', [PromotionController::class, 'saveBanner'])->name('banner');
            });

            // 2FA Management (for manager to enable/disable own 2FA)
            Route::get('/2fa/enable', [TwoFactorController::class, 'showEnable'])->name('2fa.enable');
            Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable.post');
            Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');
            Route::post('/2fa/recovery-codes', [TwoFactorController::class, 'generateRecoveryCodes'])->name('2fa.recovery-codes');

            // License
            Route::get('/license', [LicenseController::class, 'index'])->name('license');

            // Support tickets
            Route::prefix('support')->name('support.')->group(function () {
                Route::get('/', [SupportController::class, 'index'])->name('index');
                Route::post('/', [SupportController::class, 'store'])->name('store');
                Route::get('/{ticket}', [SupportController::class, 'show'])->name('show');
                Route::post('/{ticket}/reply', [SupportController::class, 'reply'])->name('reply');
                Route::post('/{ticket}/close', [SupportController::class, 'close'])->name('close');
            });

            // Fraud Detection
            Route::prefix('fraud')->name('fraud.')->group(function () {
                Route::get('/', [FraudController::class, 'index'])->name('index');
                // Blocklist routes must come before /{order} to avoid parameter conflict
                Route::prefix('blocklist')->name('blocklist.')->group(function () {
                    Route::get('/', [FraudController::class, 'blocklistIndex'])->name('index');
                    Route::post('/', [FraudController::class, 'blocklistStore'])->name('store')->middleware('permission:manage_fraud');
                    Route::delete('/{entry}', [FraudController::class, 'blocklistDestroy'])->name('destroy')->middleware('permission:manage_fraud');
                });
                Route::get('/{order}', [FraudController::class, 'show'])->name('show');
                Route::post('/{order}/release', [FraudController::class, 'release'])->name('release')->middleware('permission:manage_fraud');
                Route::post('/{order}/block', [FraudController::class, 'block'])->name('block')->middleware('permission:manage_fraud');
            });

            // Backups
            Route::prefix('backups')->name('backups.')->group(function () {
                Route::get('/', [BackupController::class, 'index'])->name('index');
                Route::post('/', [BackupController::class, 'create'])->name('create');
                Route::get('/{filename}/download', [BackupController::class, 'download'])->name('download')->where('filename', '[A-Za-z0-9._-]+');
                Route::delete('/{filename}', [BackupController::class, 'destroy'])->name('destroy')->where('filename', '[A-Za-z0-9._-]+');
            });

            // Notifications
            Route::prefix('notifications')->name('notifications.')->group(function () {
                Route::get('/', [NotificationController::class, 'index'])->name('index');
                Route::patch('/{id}/read', [NotificationController::class, 'markRead'])->name('mark-read');
                Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('mark-all-read');
            });

            // Picking list
            Route::get('/orders/picking-list', [PickingListController::class, 'generate'])->name('orders.picking-list');

            // Badges
            Route::prefix('badges')->name('badges.')->group(function () {
                Route::get('/', [BadgeController::class, 'index'])->name('index');
                Route::post('/', [BadgeController::class, 'store'])->name('store');
                Route::patch('/{badge}', [BadgeController::class, 'update'])->name('update');
                Route::delete('/{badge}', [BadgeController::class, 'destroy'])->name('destroy');
            });

            // Abandoned Carts
            Route::prefix('abandoned-carts')->name('abandoned-carts.')->group(function () {
                Route::get('/', [ManagerAbandonedCartController::class, 'index'])->name('index');
                Route::delete('/{abandonedCart}', [ManagerAbandonedCartController::class, 'destroy'])->name('destroy');
            });

            // Live Chat manager
            Route::prefix('chat')->name('chat.')->group(function () {
                Route::get('/', [ChatManagerController::class, 'index'])->name('index');
                Route::get('/{conversation}', [ChatManagerController::class, 'show'])->name('show');
                Route::post('/{conversation}/reply', [ChatManagerController::class, 'reply'])->name('reply');
                Route::patch('/{conversation}/close', [ChatManagerController::class, 'close'])->name('close');
                Route::get('/{conversation}/poll', [ChatManagerController::class, 'poll'])->name('poll');
            });
        });

        // ─── Staff Panel (fulfillment) ──────────────────────────────
        Route::prefix('staff')->name('tenant.staff.')->group(function () {
            // Fulfillment panel (order processing)
            Route::middleware(['role:fulfillment,manager'])->group(function () {
                Route::get('/fulfillment', [FulfillmentStaffController::class, 'index'])->name('fulfillment');
                Route::patch('/fulfillment/{order}/status', [FulfillmentStaffController::class, 'updateStatus'])
                    ->middleware('permission:update_order_status')
                    ->name('fulfillment.update-status');
            });

            // Warehouse panel (stock management)
            Route::middleware(['role:warehouse,manager'])->group(function () {
                Route::get('/warehouse', [WarehouseStaffController::class, 'index'])->name('warehouse');
            });

            // Staff Reports (staff submitting)
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/', [StaffReportController::class, 'index'])->name('index');
                Route::post('/', [StaffReportController::class, 'store'])->name('store');
            });

            // Knowledge Base (staff)
            Route::get('/knowledge-base', [StaffKbArticleController::class, 'index'])->name('knowledge-base.index');
            Route::get('/knowledge-base/{slug}', [StaffKbArticleController::class, 'show'])->name('knowledge-base.show');

            // Quick controls (orders_paused toggle)
            Route::post('/quick-controls', [QuickControlsController::class, 'update'])->name('quick-controls');

            // Push subscriptions
            Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
            Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
        });
    });
});
