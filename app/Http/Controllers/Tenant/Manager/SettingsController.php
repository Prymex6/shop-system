<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Models\Tenant\Setting;
use App\Services\GoogleReviewsService;
use App\Services\SmsService;
use App\Services\TenantStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Default settings with their types and descriptions.
     */
    protected array $settingsSchema = [
        // Shop info
        'shop_name' => ['type' => 'string', 'description' => 'Nazwa sklepu', 'group' => 'general'],
        'shop_owner_name' => ['type' => 'string', 'description' => 'Imię i nazwisko właściciela / nazwa firmy (do dokumentów prawnych)', 'group' => 'general'],
        'shop_phone' => ['type' => 'string', 'description' => 'Numer telefonu', 'group' => 'general'],
        'shop_email' => ['type' => 'string', 'description' => 'Adres e-mail', 'group' => 'general'],
        'shop_address' => ['type' => 'string', 'description' => 'Adres sklepu', 'group' => 'general'],
        'shop_description' => ['type' => 'string', 'description' => 'Opis sklepu', 'group' => 'general'],
        'shop_nip' => ['type' => 'string', 'description' => 'NIP sklepu (do faktur)', 'group' => 'general'],
        'currency' => ['type' => 'string', 'description' => 'Waluta sklepu (PLN/EUR/USD/GBP)', 'group' => 'general'],
        'google_place_id' => ['type' => 'string', 'description' => 'Google Place ID (do opinii z wizytówki Google)', 'group' => 'general'],

        // Appearance
        'logo_url' => ['type' => 'string', 'description' => 'URL logo sklepu', 'group' => 'appearance'],
        'favicon_url' => ['type' => 'string', 'description' => 'URL favicon', 'group' => 'appearance'],
        'hero_image_url' => ['type' => 'string', 'description' => 'URL zdjęcia hero na stronie głównej', 'group' => 'appearance'],
        'hero_title' => ['type' => 'string', 'description' => 'Tytuł na hero (domyślnie nazwa sklepu)', 'group' => 'appearance'],
        'hero_subtitle' => ['type' => 'string', 'description' => 'Podtytuł na hero', 'group' => 'appearance'],

        // About us
        'about_enabled' => ['type' => 'boolean', 'description' => 'Sekcja O nas włączona', 'group' => 'modules'],
        'about_title' => ['type' => 'string', 'description' => 'Tytuł sekcji O nas', 'group' => 'modules'],
        'about_text' => ['type' => 'string', 'description' => 'Treść sekcji O nas', 'group' => 'modules'],
        'about_image_url' => ['type' => 'string', 'description' => 'Zdjęcie w sekcji O nas', 'group' => 'modules'],

        // Gallery
        'gallery_enabled' => ['type' => 'boolean', 'description' => 'Galeria zdjęć włączona', 'group' => 'modules'],
        'gallery_title' => ['type' => 'string', 'description' => 'Tytuł sekcji galerii', 'group' => 'modules'],
        'gallery_images' => ['type' => 'json', 'description' => 'Zdjęcia w galerii (JSON)', 'group' => 'modules'],

        // Orders
        'min_order_value' => ['type' => 'string', 'description' => 'Minimalna wartość zamówienia', 'group' => 'orders'],
        'order_auto_accept' => ['type' => 'boolean', 'description' => 'Automatyczne przyjmowanie zamówień', 'group' => 'orders'],
        'orders_paused' => ['type' => 'boolean', 'description' => 'Zamówienia wstrzymane (chwilowa przerwa)', 'group' => 'orders'],
        'low_stock_threshold' => ['type' => 'integer', 'description' => 'Próg niskiego stanu magazynowego', 'group' => 'orders'],
        'free_shipping_threshold' => ['type' => 'string', 'description' => 'Darmowa dostawa od (PLN, 0 = wyłączone)', 'group' => 'orders'],
        'order_bump_product_id' => ['type' => 'integer', 'description' => 'Produkt proponowany jako dodatek w kasie (order bump)', 'group' => 'orders'],

        // Payments
        'payment_cash_on_delivery_enabled' => ['type' => 'boolean', 'description' => 'Płatność przy odbiorze (gotówka)', 'group' => 'payments'],
        'payment_bank_transfer_enabled' => ['type' => 'boolean', 'description' => 'Przelew bankowy', 'group' => 'payments'],
        'payment_online_enabled' => ['type' => 'boolean', 'description' => 'Płatność online (legacy)', 'group' => 'payments'],
        // Przelewy24
        'payment_p24_enabled' => ['type' => 'boolean', 'description' => 'Przelewy24 aktywne', 'group' => 'payments'],
        'p24_merchant_id' => ['type' => 'string', 'description' => 'Przelewy24 Merchant ID', 'group' => 'payments'],
        'p24_pos_id' => ['type' => 'string', 'description' => 'Przelewy24 POS ID', 'group' => 'payments'],
        'p24_api_key' => ['type' => 'encrypted', 'description' => 'Przelewy24 API Key', 'group' => 'payments'],
        'p24_crc' => ['type' => 'encrypted', 'description' => 'Przelewy24 CRC Key', 'group' => 'payments'],
        'p24_sandbox' => ['type' => 'boolean', 'description' => 'Przelewy24 tryb sandbox', 'group' => 'payments'],
        // PayU
        'payment_payu_enabled' => ['type' => 'boolean', 'description' => 'PayU aktywne', 'group' => 'payments'],
        'payu_pos_id' => ['type' => 'string', 'description' => 'PayU POS ID', 'group' => 'payments'],
        'payu_signature_key' => ['type' => 'encrypted', 'description' => 'PayU MD5 Signature Key', 'group' => 'payments'],
        'payu_client_id' => ['type' => 'string', 'description' => 'PayU OAuth Client ID', 'group' => 'payments'],
        'payu_client_secret' => ['type' => 'encrypted', 'description' => 'PayU OAuth Client Secret', 'group' => 'payments'],
        'payu_mode' => ['type' => 'string', 'description' => 'PayU tryb (sandbox/production)', 'group' => 'payments'],
        // Tpay
        'payment_tpay_enabled' => ['type' => 'boolean', 'description' => 'Tpay aktywne', 'group' => 'payments'],
        'tpay_client_id' => ['type' => 'string', 'description' => 'Tpay Client ID', 'group' => 'payments'],
        'tpay_client_secret' => ['type' => 'encrypted', 'description' => 'Tpay Client Secret', 'group' => 'payments'],
        'tpay_notification_secret' => ['type' => 'encrypted', 'description' => 'Tpay kod bezpieczeństwa powiadomień (Notification Security Code)', 'group' => 'payments'],
        'tpay_mode' => ['type' => 'string', 'description' => 'Tpay tryb (sandbox/production)', 'group' => 'payments'],

        // Printer
        'printer_enabled' => ['type' => 'boolean', 'description' => 'Drukowanie bonów włączone', 'group' => 'printer'],
        'printer_type' => ['type' => 'string', 'description' => 'Typ drukarki (bluetooth/network/usb)', 'group' => 'printer'],
        'printer_address' => ['type' => 'string', 'description' => 'Adres drukarki (IP lub MAC)', 'group' => 'printer'],

        // Notifications
        'notification_sound_enabled' => ['type' => 'boolean', 'description' => 'Dźwięk powiadomień', 'group' => 'notifications'],
        'notification_email_enabled' => ['type' => 'boolean', 'description' => 'Powiadomienia e-mail', 'group' => 'notifications'],
        'notification_email_address' => ['type' => 'string', 'description' => 'Adres e-mail do powiadomień', 'group' => 'notifications'],

        // SMS (SMSAPI.pl)
        'sms_enabled' => ['type' => 'boolean', 'description' => 'Powiadomienia SMS włączone', 'group' => 'sms'],
        'sms_sender_name' => ['type' => 'string', 'description' => 'Nazwa nadawcy SMS (maks. 11 znaków)', 'group' => 'sms'],

        // Theme / custom styling (Level A + B)
        'theme_primary_color' => ['type' => 'string', 'description' => 'Główny kolor akcentu (HEX)', 'group' => 'appearance'],
        'theme_font' => ['type' => 'string', 'description' => 'Czcionka witryny', 'group' => 'appearance'],
        'custom_css' => ['type' => 'string', 'description' => 'Własny CSS witryny klienta', 'group' => 'appearance'],

        // Homepage blocks (Level C)
        'homepage_blocks' => ['type' => 'json', 'description' => 'Bloki strony głównej', 'group' => 'modules'],

        // Social media
        'facebook_url' => ['type' => 'string', 'description' => 'Link do Facebook', 'group' => 'social'],
        'instagram_url' => ['type' => 'string', 'description' => 'Link do Instagram', 'group' => 'social'],
        'tiktok_url' => ['type' => 'string', 'description' => 'Link do TikTok', 'group' => 'social'],

        // Analytics & integrations
        'google_analytics_id' => ['type' => 'string', 'description' => 'Google Analytics GA4 Measurement ID (legacy key)', 'group' => 'integrations'],
        'ga4_measurement_id' => ['type' => 'string', 'description' => 'Google Analytics 4 Measurement ID (G-XXXXXXXX)', 'group' => 'integrations'],
        'facebook_pixel_id' => ['type' => 'string', 'description' => 'Facebook Pixel ID', 'group' => 'integrations'],
        'tiktok_pixel_id' => ['type' => 'string', 'description' => 'TikTok Pixel ID', 'group' => 'integrations'],

        // SMTP per tenant (#12)
        'smtp_host' => ['type' => 'string', 'description' => 'Serwer SMTP', 'group' => 'smtp'],
        'smtp_port' => ['type' => 'integer', 'description' => 'Port SMTP', 'group' => 'smtp'],
        'smtp_username' => ['type' => 'string', 'description' => 'Login SMTP', 'group' => 'smtp'],
        'smtp_password' => ['type' => 'encrypted', 'description' => 'Hasło SMTP', 'group' => 'smtp'],
        'smtp_from_address' => ['type' => 'string', 'description' => 'Adres nadawcy e-mail', 'group' => 'smtp'],
        'smtp_from_name' => ['type' => 'string', 'description' => 'Nazwa nadawcy e-mail', 'group' => 'smtp'],
        'smtp_encryption' => ['type' => 'string', 'description' => 'Szyfrowanie (tls/ssl/none)', 'group' => 'smtp'],

        // Loyalty program configuration
        'loyalty_enabled' => ['type' => 'boolean', 'description' => 'Program lojalnościowy włączony', 'group' => 'loyalty'],
        'loyalty_earn_mode' => ['type' => 'string', 'description' => 'Tryb naliczania: per_pln / per_order / tiered', 'group' => 'loyalty'],
        'loyalty_points_per_pln' => ['type' => 'integer', 'description' => 'Punkty za 1 PLN zamówienia', 'group' => 'loyalty'],
        'loyalty_points_per_order' => ['type' => 'integer', 'description' => 'Stała liczba pkt za zamówienie (tryb per_order)', 'group' => 'loyalty'],
        'loyalty_tiers' => ['type' => 'json', 'description' => 'Progi poziomów lojalnościowych', 'group' => 'loyalty'],
        'loyalty_tier_basis' => ['type' => 'string', 'description' => 'Podstawa poziomów: ever_earned / current_balance', 'group' => 'loyalty'],
        'loyalty_points_expiry_days' => ['type' => 'integer', 'description' => 'Wygasanie punktów (dni, 0 = brak)', 'group' => 'loyalty'],
        'loyalty_expiry_warning_days' => ['type' => 'integer', 'description' => 'Ostrzeżenie o wygasaniu (dni przed)', 'group' => 'loyalty'],
        'loyalty_bonus_registration' => ['type' => 'integer', 'description' => 'Bonus za rejestrację (pkt)', 'group' => 'loyalty'],
        'loyalty_bonus_first_order' => ['type' => 'integer', 'description' => 'Bonus za pierwsze zamówienie (pkt)', 'group' => 'loyalty'],
        'loyalty_bonus_referral' => ['type' => 'integer', 'description' => 'Bonus za polecenie (pkt)', 'group' => 'loyalty'],
        'loyalty_bonus_birthday' => ['type' => 'integer', 'description' => 'Bonus urodzinowy (pkt)', 'group' => 'loyalty'],
        'loyalty_bonus_birthday_multiplier' => ['type' => 'float', 'description' => 'Mnożnik zamówień w miesiącu urodzin', 'group' => 'loyalty'],

        // Regulations / terms (#14)
        'terms_content' => ['type' => 'string', 'description' => 'Treść regulaminu (HTML)', 'group' => 'regulations'],
        'privacy_content' => ['type' => 'string', 'description' => 'Treść polityki prywatności (HTML)', 'group' => 'regulations'],
        'shipping_content' => ['type' => 'string', 'description' => 'Informacje o dostawie i płatnościach (HTML)', 'group' => 'regulations'],
        'returns_content' => ['type' => 'string', 'description' => 'Polityka zwrotów i reklamacji (HTML)', 'group' => 'regulations'],
        'faq_content' => ['type' => 'string', 'description' => 'FAQ – najczęstsze pytania (HTML)', 'group' => 'regulations'],

        // Vacation / maintenance mode (#34)
        'vacation_mode' => ['type' => 'boolean', 'description' => 'Tryb urlopowy – blokada zamówień', 'group' => 'general'],
        'vacation_message' => ['type' => 'string', 'description' => 'Komunikat wyświetlany podczas trybu urlopowego', 'group' => 'general'],

        // Announcement Banner
        'announcement_enabled' => ['type' => 'boolean', 'description' => 'Baner z ogłoszeniem włączony', 'group' => 'general'],
        'announcement_text' => ['type' => 'string', 'description' => 'Treść ogłoszenia w banerze', 'group' => 'general'],
        'announcement_color' => ['type' => 'string', 'description' => 'Kolor tła banera (HEX)', 'group' => 'general'],

        // Newsletter
        'newsletter_enabled' => ['type' => 'boolean', 'description' => 'Sekcja newslettera włączona', 'group' => 'modules'],
        'newsletter_title' => ['type' => 'string',  'description' => 'Tytuł sekcji newslettera', 'group' => 'modules'],
        'newsletter_text' => ['type' => 'string',  'description' => 'Opis sekcji newslettera', 'group' => 'modules'],

        // Trust badges
        'trust_badges_enabled' => ['type' => 'boolean', 'description' => 'Sekcja ikon zaufania włączona', 'group' => 'modules'],
        'trust_badges' => ['type' => 'json',    'description' => 'Lista ikon zaufania (JSON)', 'group' => 'modules'],

        // Reviews section
        'reviews_section_enabled' => ['type' => 'boolean', 'description' => 'Sekcja opinii na stronie głównej włączona', 'group' => 'modules'],

        // Reports
        'weekly_report_enabled' => ['type' => 'boolean', 'description' => 'Wysyłaj cotygodniowe raporty e-mail do managera', 'group' => 'notifications'],
        'inpost_api_token' => ['type' => 'encrypted', 'description' => 'Token API InPost', 'group' => 'integrations'],
        'inpost_organization_id' => ['type' => 'string', 'description' => 'ID organizacji InPost', 'group' => 'integrations'],
        'smsapi_token' => ['type' => 'encrypted', 'description' => 'Token SMSAPI (OAuth2)', 'group' => 'sms'],
        'trustpilot_business_id' => ['type' => 'string', 'description' => 'Trustpilot Business ID', 'group' => 'integrations'],

        // Language & multi-currency
        'shop_language' => ['type' => 'string', 'description' => 'Język panelu i sklepu (pl/en)', 'group' => 'general'],
        'enabled_currencies' => ['type' => 'json', 'description' => 'Aktywne waluty', 'group' => 'general'],

        // Reviews moderation
        'reviews_require_approval' => ['type' => 'boolean', 'description' => 'Recenzje wymagają zatwierdzenia', 'group' => 'orders'],
        'reviews_min_order_required' => ['type' => 'boolean', 'description' => 'Recenzja tylko po zakupie', 'group' => 'orders'],

        // Refund & RMA policies
        'refund_window_days' => ['type' => 'integer', 'description' => 'Czas na zwrot (dni)', 'group' => 'orders'],
        'rma_enabled' => ['type' => 'boolean', 'description' => 'Zwroty (RMA) włączone', 'group' => 'orders'],
        'rma_window_days' => ['type' => 'integer', 'description' => 'Czas na zgłoszenie RMA (dni)', 'group' => 'orders'],
        'rma_auto_approve' => ['type' => 'boolean', 'description' => 'Automatyczne zatwierdzanie RMA', 'group' => 'orders'],

        // Live Chat
        'chat_enabled' => ['type' => 'boolean', 'description' => 'Live Chat włączony', 'group' => 'notifications'],
        'chat_greeting' => ['type' => 'string', 'description' => 'Wiadomość powitalna w chacie', 'group' => 'notifications'],

        // Gift Cards
        'gift_cards_enabled' => ['type' => 'boolean', 'description' => 'Karty podarunkowe włączone', 'group' => 'orders'],
        'gift_card_expiry_days' => ['type' => 'integer', 'description' => 'Ważność kart podarunkowych (dni, 0=bezterminowo)', 'group' => 'orders'],

        // Fraud detection
        'fraud_detection_enabled' => ['type' => 'boolean', 'description' => 'Automatyczne wykrywanie fraudów', 'group' => 'orders'],
        'fraud_auto_block' => ['type' => 'boolean', 'description' => 'Automatyczne blokowanie podejrzanych zamówień', 'group' => 'orders'],
        // AI
        'ai_provider' => ['type' => 'string',  'description' => 'Dostawca AI (openai/anthropic)', 'group' => 'ai'],
        'ai_api_key' => ['type' => 'string',  'description' => 'Klucz API do generowania opisów', 'group' => 'ai'],
        'ai_model' => ['type' => 'string',  'description' => 'Model AI (opcjonalnie)', 'group' => 'ai'],
        // Urgency CTA
        'urgency_countdown_enabled' => ['type' => 'boolean', 'description' => 'Odliczanie do końca promocji/flash sale', 'group' => 'urgency'],
        'urgency_stock_enabled' => ['type' => 'boolean', 'description' => 'Ostrzeżenie o niskim stanie magazynu', 'group' => 'urgency'],
        'urgency_stock_threshold' => ['type' => 'integer', 'description' => 'Próg niskiego stanu (szt.)', 'group' => 'urgency'],
        'urgency_viewers_enabled' => ['type' => 'boolean', 'description' => 'Licznik oglądających', 'group' => 'urgency'],
        'urgency_viewers_min' => ['type' => 'integer', 'description' => 'Min. liczba oglądających (losowo)', 'group' => 'urgency'],
        'urgency_viewers_max' => ['type' => 'integer', 'description' => 'Max. liczba oglądających (losowo)', 'group' => 'urgency'],
        'urgency_sold_enabled' => ['type' => 'boolean', 'description' => 'Licznik sprzedanych (ostatnie 24h)', 'group' => 'urgency'],
        'urgency_sold_min' => ['type' => 'integer', 'description' => 'Min. sprzedanych (losowo)', 'group' => 'urgency'],
        'urgency_sold_max' => ['type' => 'integer', 'description' => 'Max. sprzedanych (losowo)', 'group' => 'urgency'],
        'urgency_delivery_enabled' => ['type' => 'boolean', 'description' => 'Timer "zamów do X — wyślemy dziś"', 'group' => 'urgency'],
        'urgency_delivery_cutoff' => ['type' => 'string',  'description' => 'Godzina graniczna wysyłki (np. 14:00)', 'group' => 'urgency'],
    ];

    public function index()
    {
        $settings = Setting::getAllAsArray();

        // Fill in defaults for missing settings
        $defaults = $this->getDefaults();
        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $settings)) {
                $settings[$key] = $value;
            }
        }

        // API secrets / SMTP password / SMS token must never be decrypted and
        // sent to the browser — the settings form is one giant object spread
        // straight into a single save request, so a masked placeholder here
        // would round-trip back and overwrite the real secret on next save.
        // Send blank instead, plus a flag the UI can use to show "already set".
        // (Derived from the schema's own 'encrypted' type, not the narrower/
        // stale Setting::ENCRYPTED_KEYS const, which is missing several of
        // these field names and would leave them unmasked.)
        $secretsConfigured = [];
        foreach ($this->encryptedSettingKeys() as $key) {
            $secretsConfigured[$key] = !empty($settings[$key] ?? null);
            $settings[$key] = '';
        }

        return Inertia::render('Tenant/Manager/Settings/Index', [
            'settings' => $settings,
            'schema' => $this->settingsSchema,
            'secretsConfigured' => $secretsConfigured,
            'products' => Product::published()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'shop_name' => 'nullable|string|max:255',
            'shop_owner_name' => 'nullable|string|max:500',
            'shop_phone' => 'nullable|string|max:30',
            'shop_email' => 'nullable|email|max:255',
            'shop_address' => 'nullable|string|max:500',
            'shop_description' => 'nullable|string|max:1000',
            'shop_nip' => 'nullable|string|max:20',
            'currency' => 'nullable|string|in:PLN,EUR,USD,GBP',
            'google_place_id' => 'nullable|string|max:255',
            // Appearance
            'logo_url' => 'nullable|string|max:500',
            'favicon_url' => 'nullable|string|max:500',
            'hero_image_url' => 'nullable|string|max:500',
            'hero_title' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string|max:500',
            // Modules
            'about_enabled' => 'nullable|boolean',
            'about_title' => 'nullable|string|max:255',
            'about_text' => 'nullable|string|max:5000',
            'about_image_url' => 'nullable|string|max:500',
            'gallery_enabled' => 'nullable|boolean',
            'gallery_title' => 'nullable|string|max:255',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'nullable|string|max:500',
            // Orders
            'min_order_value' => 'nullable|numeric|min:0',
            'order_auto_accept' => 'nullable|boolean',
            'orders_paused' => 'nullable|boolean',
            'low_stock_threshold' => 'nullable|integer|min:0|max:9999',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'order_bump_product_id' => 'nullable|integer|exists:products,id',
            'payment_cash_on_delivery_enabled' => 'nullable|boolean',
            'payment_bank_transfer_enabled' => 'nullable|boolean',
            'payment_online_enabled' => 'nullable|boolean',
            'payment_p24_enabled' => 'nullable|boolean',
            'p24_merchant_id' => 'nullable|string|max:100',
            'p24_pos_id' => 'nullable|string|max:100',
            'p24_api_key' => 'nullable|string|max:200',
            'p24_crc' => 'nullable|string|max:100',
            'p24_sandbox' => 'nullable|boolean',
            'payment_payu_enabled' => 'nullable|boolean',
            'payu_pos_id' => 'nullable|string|max:100',
            'payu_signature_key' => 'nullable|string|max:200',
            'payu_client_id' => 'nullable|string|max:200',
            'payu_client_secret' => 'nullable|string|max:200',
            'payu_mode' => 'nullable|string|in:sandbox,production',
            'payment_tpay_enabled' => 'nullable|boolean',
            'tpay_client_id' => 'nullable|string|max:200',
            'tpay_client_secret' => 'nullable|string|max:200',
            'tpay_notification_secret' => 'nullable|string|max:200',
            'tpay_mode' => 'nullable|string|in:sandbox,production',
            'printer_enabled' => 'nullable|boolean',
            'printer_type' => 'nullable|string|in:bluetooth,network,usb',
            'printer_address' => 'nullable|string|max:255',
            'notification_sound_enabled' => 'nullable|boolean',
            'notification_email_enabled' => 'nullable|boolean',
            'notification_email_address' => 'nullable|email|max:255',
            // SMS
            'sms_enabled' => 'nullable|boolean',
            'smsapi_token' => 'nullable|string|max:500',
            'sms_sender_name' => 'nullable|string|max:11',
            // Theme
            'theme_primary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_font' => 'nullable|string|in:inter,roboto,merriweather,playfair',
            'custom_css' => ['nullable', 'string', 'max:50000', 'not_regex:/<\/style/i'],
            // Social media
            'facebook_url' => ['nullable', 'url', 'max:500'],
            'instagram_url' => ['nullable', 'url', 'max:500'],
            'tiktok_url' => ['nullable', 'url', 'max:500'],
            // Analytics & integrations
            'google_analytics_id' => ['nullable', 'string', 'max:50', 'regex:/^(G-[A-Z0-9]{4,20}|UA-\d{4,12}-\d{1,4}|GT-[A-Z0-9]{4,20}|AW-[0-9]{8,12})$/'],
            'ga4_measurement_id' => ['nullable', 'string', 'max:50', 'regex:/^G-[A-Z0-9]{4,20}$/'],
            'facebook_pixel_id' => ['nullable', 'string', 'max:20', 'regex:/^\d{10,20}$/'],
            'tiktok_pixel_id' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9]{15,30}$/'],
            // SMTP
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_from_address' => 'nullable|email|max:255',
            'smtp_from_name' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|string|in:tls,ssl,none',
            // Loyalty v2
            'loyalty_enabled' => 'nullable|boolean',
            'loyalty_earn_mode' => 'nullable|string|in:per_pln,per_order,tiered',
            'loyalty_points_per_pln' => 'nullable|integer|min:1|max:100',
            'loyalty_points_per_order' => 'nullable|integer|min:0',
            'loyalty_tiers' => 'nullable|array',
            'loyalty_tier_basis' => 'nullable|string|in:ever_earned,current_balance',
            'loyalty_points_expiry_days' => 'nullable|integer|min:0',
            'loyalty_expiry_warning_days' => 'nullable|integer|min:0',
            'loyalty_bonus_registration' => 'nullable|integer|min:0',
            'loyalty_bonus_first_order' => 'nullable|integer|min:0',
            'loyalty_bonus_referral' => 'nullable|integer|min:0',
            'loyalty_bonus_birthday' => 'nullable|integer|min:0',
            'loyalty_bonus_birthday_multiplier' => 'nullable|numeric|min:1|max:10',
            // Terms / Privacy / Legal
            'terms_content' => 'nullable|string|max:100000',
            'privacy_content' => 'nullable|string|max:100000',
            'shipping_content' => 'nullable|string|max:100000',
            'returns_content' => 'nullable|string|max:100000',
            'faq_content' => 'nullable|string|max:100000',
            // Vacation mode
            'vacation_mode' => 'nullable|boolean',
            'vacation_message' => 'nullable|string|max:500',
            // Announcement Banner
            'announcement_enabled' => 'nullable|boolean',
            'announcement_text' => 'nullable|string|max:500',
            'announcement_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            // Newsletter
            'newsletter_enabled' => 'nullable|boolean',
            'newsletter_title' => 'nullable|string|max:255',
            'newsletter_text' => 'nullable|string|max:500',
            // Trust badges
            'trust_badges_enabled' => 'nullable|boolean',
            'trust_badges' => 'nullable|array',
            'trust_badges.*.icon' => 'nullable|string|max:64',
            'trust_badges.*.text' => 'nullable|string|max:255',
            // Reviews section
            'reviews_section_enabled' => 'nullable|boolean',
            // Homepage blocks
            'homepage_blocks' => 'nullable|array',
            'homepage_blocks.*.id' => 'nullable|string|max:64',
            'homepage_blocks.*.type' => 'nullable|string|in:announcement,promo_text,cta',
            'homepage_blocks.*.enabled' => 'nullable|boolean',
            'homepage_blocks.*.title' => 'nullable|string|max:255',
            'homepage_blocks.*.content' => 'nullable|string|max:2000',
            'homepage_blocks.*.bg_color' => 'nullable|string|max:20',
            'homepage_blocks.*.link_url' => ['nullable', 'string', 'max:500', 'not_regex:/^javascript:/i'],
            'homepage_blocks.*.link_text' => 'nullable|string|max:100',
            // Language & currency
            'shop_language' => 'nullable|string|in:pl,en',
            'enabled_currencies' => 'nullable|array',
            'enabled_currencies.*' => 'nullable|string|in:PLN,EUR,USD,GBP,CZK',
            'trustpilot_business_id' => 'nullable|string|max:100',
            // Reviews
            'reviews_require_approval' => 'nullable|boolean',
            'reviews_min_order_required' => 'nullable|boolean',
            // Refund & RMA
            'refund_window_days' => 'nullable|integer|min:0|max:365',
            'rma_enabled' => 'nullable|boolean',
            'rma_window_days' => 'nullable|integer|min:0|max:365',
            'rma_auto_approve' => 'nullable|boolean',
            // Chat
            'chat_enabled' => 'nullable|boolean',
            'chat_greeting' => 'nullable|string|max:255',
            // Gift cards
            'gift_cards_enabled' => 'nullable|boolean',
            'gift_card_expiry_days' => 'nullable|integer|min:0',
            // Fraud
            'fraud_detection_enabled' => 'nullable|boolean',
            'fraud_auto_block' => 'nullable|boolean',
            // Urgency CTA
            'urgency_countdown_enabled' => 'nullable|boolean',
            'urgency_stock_enabled' => 'nullable|boolean',
            'urgency_stock_threshold' => 'nullable|integer|min:1|max:100',
            'urgency_viewers_enabled' => 'nullable|boolean',
            'urgency_viewers_min' => 'nullable|integer|min:1|max:999',
            'urgency_viewers_max' => 'nullable|integer|min:1|max:999',
            'urgency_sold_enabled' => 'nullable|boolean',
            'urgency_sold_min' => 'nullable|integer|min:1|max:9999',
            'urgency_sold_max' => 'nullable|integer|min:1|max:9999',
            'urgency_delivery_enabled' => 'nullable|boolean',
            'urgency_delivery_cutoff' => 'nullable|string|max:5',
            // AI
            'ai_provider' => 'nullable|string|in:openai,anthropic',
            'ai_api_key' => 'nullable|string|max:200',
            'ai_model' => 'nullable|string|max:100',
        ]);

        if (array_key_exists('shop_phone', $data)) {
            $data['shop_phone'] = PhoneHelper::normalize($data['shop_phone']) ?? '';
        }

        // Online payment gateways, thermal printer and SMS are test-version only
        if ((tenancy()->tenant?->version ?? 'stable') !== 'test') {
            $testOnlyKeys = [
                'payment_p24_enabled', 'p24_merchant_id', 'p24_pos_id', 'p24_api_key', 'p24_crc', 'p24_sandbox',
                'payment_payu_enabled', 'payu_pos_id', 'payu_signature_key', 'payu_client_id', 'payu_client_secret', 'payu_mode',
                'payment_tpay_enabled', 'tpay_client_id', 'tpay_client_secret', 'tpay_notification_secret', 'tpay_mode',
                'printer_enabled', 'printer_type', 'printer_address',
                'sms_enabled', 'smsapi_token', 'sms_sender_name',
            ];
            foreach ($testOnlyKeys as $key) {
                unset($data[$key]);
            }
        }

        // Plan feature "custom_css" (LandlordSeeder — off on Starter/Basic)
        // was never checked: any plan could inject arbitrary site-wide CSS.
        // A lookup failure never blocks saving the rest of the settings form.
        try {
            $plan = tenancy()->tenant?->plan;
            if ($plan && !$plan->hasFeature('custom_css')) {
                unset($data['custom_css']);
            }
        } catch (\Exception $e) {
            Log::warning('Plan custom_css feature check failed, allowing save: ' . $e->getMessage());
        }

        // These render unsanitized via v-html on public, unauthenticated pages
        // (/regulamin, /polityka-prywatnosci, ...) — purify before persisting so
        // a compromised or malicious manager account can't stored-XSS every
        // visitor who opens the store's legal pages.
        $htmlFields = ['terms_content', 'privacy_content', 'shipping_content', 'returns_content', 'faq_content'];
        foreach ($htmlFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $data[$field] = clean($data[$field], 'default');
            }
        }

        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $this->settingsSchema)) {
                continue;
            }

            // Encrypted secret fields are sent to the browser blank (see index()),
            // so a blank submission means "left untouched", not "clear it" —
            // otherwise saving any other tab would wipe out configured API keys.
            if (in_array($key, $this->encryptedSettingKeys(), true) && ($value === null || $value === '')) {
                continue;
            }

            $schema = $this->settingsSchema[$key];
            Setting::set($key, $value, $schema['type'], $schema['description']);
        }

        if (array_key_exists('google_place_id', $data) && class_exists(GoogleReviewsService::class)) {
            app(GoogleReviewsService::class)->clearCache();
        }

        Log::info('Settings: ustawienia zapisane', [
            'keys' => array_keys($data),
            'manager_id' => auth('tenant')->id(),
        ]);

        return redirect()->route('tenant.manager.settings.index')
            ->with('success', __('messages.settings_saved'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'field' => 'required|string',
        ]);

        $subfolder = match (true) {
            str_starts_with($request->field, 'product') => 'products',
            str_starts_with($request->field, 'gallery') => 'gallery',
            default => 'settings',
        };

        $uploadedFile = $request->file('file');
        $filename = Str::uuid() . '.jpg';
        $tenantId = tenancy()->tenant?->id;

        if ($tenantId) {
            $storage = app(TenantStorageService::class);
            $absolutePath = $storage->uploadDestination($tenantId, $subfolder, $filename);
            $url = $storage->publicUrl($tenantId, $subfolder, $filename);
        } else {
            $destDir = public_path("uploads/{$subfolder}");
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            $absolutePath = "{$destDir}/{$filename}";
            $url = "/uploads/{$subfolder}/{$filename}";
        }

        $this->optimizeAndSave($uploadedFile->getRealPath(), $uploadedFile->getMimeType(), $absolutePath);

        return response()->json(['url' => $url]);
    }

    private function optimizeAndSave(string $sourcePath, string $mimeType, string $destPath): void
    {
        // The upload() validator already required the 'image' rule (real
        // getimagesize() content check, not just a mimes/extension guess),
        // so both fallback branches below are copying bytes already known
        // to be a genuine image — not an arbitrary payload. The residual
        // risk they skip hardening against is a polyglot file (valid image
        // header + payload appended after it, invisible to getimagesize()
        // but stripped by a full GD re-encode) — logged here so it's at
        // least operator-visible instead of silently happening forever.
        if (!extension_loaded('gd')) {
            Log::warning('SettingsController: GD extension unavailable, saving upload without re-encoding (polyglot-file hardening skipped)', ['dest' => $destPath]);
            copy($sourcePath, $destPath);

            return;
        }

        $src = match ($mimeType) {
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/gif' => @imagecreatefromgif($sourcePath),
            'image/webp' => @imagecreatefromwebp($sourcePath),
            default => @imagecreatefromjpeg($sourcePath),
        };

        if (!$src) {
            Log::warning('SettingsController: GD could not decode an upload that passed image validation, saving without re-encoding', ['dest' => $destPath, 'mime' => $mimeType]);
            copy($sourcePath, $destPath);

            return;
        }

        $origW = imagesx($src);
        $origH = imagesy($src);
        $maxDim = 1200;

        if ($origW > $maxDim || $origH > $maxDim) {
            $ratio = min($maxDim / $origW, $maxDim / $origH);
            $newW = (int) round($origW * $ratio);
            $newH = (int) round($origH * $ratio);
            $dst = imagecreatetruecolor($newW, $newH);
            // Preserve transparency for PNG/GIF
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($src);
            $src = $dst;
        }

        imagejpeg($src, $destPath, 80);
        imagedestroy($src);
    }

    /** Setting keys whose value is stored encrypted (type === 'encrypted' in the schema). */
    protected function encryptedSettingKeys(): array
    {
        return array_keys(array_filter(
            $this->settingsSchema,
            fn ($schema) => ($schema['type'] ?? null) === 'encrypted'
        ));
    }

    protected function getDefaults(): array
    {
        return [
            'shop_name' => '',
            'shop_owner_name' => '',
            'shop_phone' => '',
            'shop_email' => '',
            'shop_address' => '',
            'shop_description' => '',
            'shop_nip' => '',
            'currency' => 'PLN',
            'google_place_id' => '',
            // Appearance
            'logo_url' => '',
            'favicon_url' => '',
            'hero_image_url' => '',
            'hero_title' => '',
            'hero_subtitle' => '',
            // Modules
            'about_enabled' => false,
            'about_title' => 'O nas',
            'about_text' => '',
            'about_image_url' => '',
            'gallery_enabled' => false,
            'gallery_title' => 'Galeria',
            'gallery_images' => [],
            'min_order_value' => '0.00',
            'order_auto_accept' => false,
            'orders_paused' => false,
            'low_stock_threshold' => 5,
            'free_shipping_threshold' => '0',
            'order_bump_product_id' => null,
            'payment_cash_on_delivery_enabled' => false,
            'payment_bank_transfer_enabled' => false,
            'payment_online_enabled' => false,
            'p24_merchant_id' => '',
            'p24_pos_id' => '',
            'p24_crc' => '',
            'p24_sandbox' => true,
            'printer_enabled' => false,
            'printer_type' => 'network',
            'printer_address' => '',
            'notification_sound_enabled' => true,
            'notification_email_enabled' => false,
            'notification_email_address' => '',
            // SMS
            'sms_enabled' => false,
            'smsapi_token' => '',
            'sms_sender_name' => 'Sklep',
            // Theme
            'theme_primary_color' => '#4f46e5',
            'theme_font' => 'inter',
            'custom_css' => '',
            // Homepage blocks
            'homepage_blocks' => [],
            // Social media
            'facebook_url' => '',
            'instagram_url' => '',
            'tiktok_url' => '',
            // Analytics
            'google_analytics_id' => '',
            'ga4_measurement_id' => '',
            'facebook_pixel_id' => '',
            'tiktok_pixel_id' => '',
            // SMTP
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_from_address' => '',
            'smtp_from_name' => '',
            'smtp_encryption' => 'tls',
            // Loyalty v2
            'loyalty_enabled' => false,
            'loyalty_earn_mode' => 'per_pln',
            'loyalty_points_per_pln' => 1,
            'loyalty_points_per_order' => 10,
            'loyalty_tier_basis' => 'ever_earned',
            'loyalty_points_expiry_days' => 0,
            'loyalty_expiry_warning_days' => 7,
            'loyalty_bonus_registration' => 50,
            'loyalty_bonus_first_order' => 100,
            'loyalty_bonus_referral' => 200,
            'loyalty_bonus_birthday' => 150,
            'loyalty_bonus_birthday_multiplier' => 2.0,
            'loyalty_tiers' => [
                'bronze' => ['min' => 0,     'name' => 'Brąz',    'color' => '#cd7f32', 'multiplier' => 1.0,  'monthly_bonus' => 0,    'delivery_bonus' => 0],
                'silver' => ['min' => 500,   'name' => 'Srebro',  'color' => '#9ca3af', 'multiplier' => 1.25, 'monthly_bonus' => 50,   'delivery_bonus' => 10],
                'gold' => ['min' => 1500,  'name' => 'Złoto',   'color' => '#f59e0b', 'multiplier' => 1.5,  'monthly_bonus' => 150,  'delivery_bonus' => 20],
                'platinum' => ['min' => 4000,  'name' => 'Platyna', 'color' => '#60a5fa', 'multiplier' => 2.0,  'monthly_bonus' => 400,  'delivery_bonus' => 999],
                'diamond' => ['min' => 10000, 'name' => 'Diament', 'color' => '#a78bfa', 'multiplier' => 3.0,  'monthly_bonus' => 1000, 'delivery_bonus' => 999],
            ],
            // Terms / Privacy / Legal
            'terms_content' => '',
            'privacy_content' => '',
            'shipping_content' => '',
            'returns_content' => '',
            'faq_content' => '',
            // Vacation mode
            'vacation_mode' => false,
            'vacation_message' => 'Sklep jest chwilowo niedostępny. Zapraszamy wkrótce!',

            // Announcement Banner
            'announcement_enabled' => false,
            'announcement_text' => '',
            'announcement_color' => '#4F46E5',
            // Newsletter
            'newsletter_enabled' => false,
            'newsletter_title' => 'Bądź na bieżąco!',
            'newsletter_text' => '',
            // Trust badges
            'trust_badges_enabled' => false,
            'trust_badges' => [],
            // Reviews section
            'reviews_section_enabled' => true,
            // Language & currency
            'shop_language' => 'pl',
            'enabled_currencies' => ['PLN'],
            'trustpilot_business_id' => '',
            // Reviews moderation
            'reviews_require_approval' => true,
            'reviews_min_order_required' => true,
            // Refund & RMA
            'refund_window_days' => 14,
            'rma_enabled' => true,
            'rma_window_days' => 30,
            'rma_auto_approve' => false,
            // Chat
            'chat_enabled' => true,
            'chat_greeting' => 'Cześć! Jak możemy Ci pomóc?',
            // Gift cards
            'gift_cards_enabled' => false,
            'gift_card_expiry_days' => 365,
            // Fraud
            'fraud_detection_enabled' => true,
            'fraud_auto_block' => false,
        ];
    }

    public function testSms(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        try {
            $smsService = app(SmsService::class);
            $sent = $smsService->send(
                $request->phone,
                'Wiadomość testowa z systemu ' . config('app.name') . '. Konfiguracja SMS działa poprawnie.'
            );

            if ($sent) {
                return response()->json(['success' => true, 'message' => __('messages.sms_test_sent')]);
            }

            return response()->json(['success' => false, 'message' => __('messages.sms_failed')], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('messages.sms_failed_reason', ['reason' => $e->getMessage()])], 422);
        }
    }

    public function testSmtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $encryption = Setting::get('smtp_encryption', 'tls');

            config(['mail.mailers.tenant_smtp_test' => [
                'transport' => 'smtp',
                'host' => Setting::get('smtp_host'),
                'port' => (int) Setting::get('smtp_port', 587),
                'encryption' => $encryption !== 'none' ? $encryption : null,
                'username' => Setting::get('smtp_username'),
                'password' => Setting::get('smtp_password'),
                'from' => [
                    'address' => Setting::get('smtp_from_address', Setting::get('shop_email', 'noreply@example.com')),
                    'name' => Setting::get('smtp_from_name', Setting::get('shop_name', 'Sklep')),
                ],
            ]]);

            Mail::mailer('tenant_smtp_test')
                ->raw('To jest wiadomość testowa z systemu ' . config('app.name') . '.', function ($message) use ($request) {
                    $message->to($request->email)->subject('Test konfiguracji SMTP');
                });

            return response()->json(['success' => true, 'message' => __('messages.test_message_sent')]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => __('messages.smtp_failed', ['reason' => $e->getMessage()])], 422);
        }
    }
}
