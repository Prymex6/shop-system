<?php

namespace Database\Seeders;

use App\Models\Tenant\Setting;
use Illuminate\Database\Seeder;

class TenantSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ── Shop info ──────────────────────────────────────────────────────
            ['key' => 'shop_name',        'value' => __('messages.shop_default_name'),  'type' => 'string',  'description' => 'Shop name'],
            ['key' => 'shop_phone',       'value' => '+48 123 456 789',   'type' => 'string',  'description' => 'Phone number'],
            ['key' => 'shop_email',       'value' => '',                  'type' => 'string',  'description' => 'Shop email address'],
            ['key' => 'shop_address',     'value' => '',                  'type' => 'string',  'description' => 'Shop address'],
            ['key' => 'shop_nip',         'value' => '',                  'type' => 'string',  'description' => 'Company tax ID'],
            ['key' => 'shop_description', 'value' => __('messages.shop_default_short_description'), 'type' => 'string', 'description' => 'Shop description'],
            ['key' => 'google_place_id',  'value' => '',                  'type' => 'string',  'description' => 'Google Place ID (for reviews from the Google listing)'],

            // ── Appearance ────────────────────────────────────────────────────
            ['key' => 'logo_url',           'value' => '/images/logo.png',    'type' => 'string', 'description' => 'Shop logo URL'],
            ['key' => 'favicon_url',        'value' => '/images/favicon.png', 'type' => 'string', 'description' => 'Favicon URL'],
            ['key' => 'hero_image_url',     'value' => '/images/hero.png',    'type' => 'string', 'description' => 'Homepage hero image URL'],
            ['key' => 'hero_title',         'value' => __('messages.shop_default_name'),    'type' => 'string', 'description' => 'Hero title'],
            ['key' => 'hero_subtitle',      'value' => __('messages.shop_default_hero_subtitle'), 'type' => 'string', 'description' => 'Hero subtitle'],
            ['key' => 'theme_primary_color', 'value' => '#4f46e5',              'type' => 'string', 'description' => 'Primary accent colour (HEX)'],
            ['key' => 'theme_font',         'value' => 'inter',                'type' => 'string', 'description' => 'Storefront font'],
            ['key' => 'custom_css',         'value' => '',                     'type' => 'string', 'description' => 'Custom CSS for the storefront'],

            // ── Opening hours ─────────────────────────────────────────────────
            [
                'key' => 'opening_hours',
                'value' => json_encode([
                    'monday' => ['open' => '08:00', 'close' => '20:00', 'enabled' => true],
                    'tuesday' => ['open' => '08:00', 'close' => '20:00', 'enabled' => true],
                    'wednesday' => ['open' => '08:00', 'close' => '20:00', 'enabled' => true],
                    'thursday' => ['open' => '08:00', 'close' => '20:00', 'enabled' => true],
                    'friday' => ['open' => '08:00', 'close' => '20:00', 'enabled' => true],
                    'saturday' => ['open' => '09:00', 'close' => '16:00', 'enabled' => true],
                    'sunday' => ['open' => '00:00', 'close' => '00:00', 'enabled' => false],
                ]),
                'type' => 'json',
                'description' => 'Hours during which orders are taken',
            ],

            // ── Orders ────────────────────────────────────────────────────────
            ['key' => 'min_order_value',  'value' => '0.00', 'type' => 'string',  'description' => 'Minimum order value (PLN)'],
            ['key' => 'order_auto_accept', 'value' => '1',    'type' => 'boolean', 'description' => 'Accept orders automatically'],
            ['key' => 'low_stock_threshold', 'value' => '5', 'type' => 'integer', 'description' => 'Low stock threshold'],

            // ── Payments ──────────────────────────────────────────────────────
            ['key' => 'payment_cash_on_delivery_enabled', 'value' => '0', 'type' => 'boolean', 'description' => 'Cash on delivery'],
            ['key' => 'payment_bank_transfer_enabled',    'value' => '0', 'type' => 'boolean', 'description' => 'Przelew bankowy'],
            ['key' => 'payment_online_enabled',           'value' => '0', 'type' => 'boolean', 'description' => 'Online payment (legacy)'],
            ['key' => 'payment_p24_enabled',              'value' => '0', 'type' => 'boolean', 'description' => 'Przelewy24 aktywne'],
            ['key' => 'p24_merchant_id',                  'value' => '', 'type' => 'string',   'description' => 'Przelewy24 Merchant ID'],
            ['key' => 'p24_pos_id',                       'value' => '', 'type' => 'string',   'description' => 'Przelewy24 POS ID'],
            ['key' => 'p24_crc',                          'value' => '', 'type' => 'string',   'description' => 'Przelewy24 CRC Key'],
            ['key' => 'p24_sandbox',                      'value' => '1', 'type' => 'boolean', 'description' => 'Przelewy24 tryb sandbox'],

            // ── Notifications ─────────────────────────────────────────────────
            ['key' => 'notification_sound_enabled', 'value' => '1',  'type' => 'boolean', 'description' => 'Notification sound'],
            ['key' => 'notification_email_enabled', 'value' => '0',  'type' => 'boolean', 'description' => 'Powiadomienia e-mail'],
            ['key' => 'notification_email_address', 'value' => '',   'type' => 'string',  'description' => 'Email address for notifications'],

            // ── SMS ───────────────────────────────────────────────────────────
            ['key' => 'sms_enabled',     'value' => '0',   'type' => 'boolean', 'description' => 'SMS notifications enabled'],
            ['key' => 'smsapi_token',    'value' => '',    'type' => 'string',  'description' => 'Token API SMSAPI.pl (OAuth2)'],
            ['key' => 'sms_sender_name', 'value' => 'Sklep', 'type' => 'string', 'description' => 'SMS sender name (11 characters at most)'],

            // ── About us ──────────────────────────────────────────────────────
            ['key' => 'about_enabled',   'value' => '1',   'type' => 'boolean', 'description' => 'About section enabled'],
            ['key' => 'about_title',     'value' => 'O nas', 'type' => 'string', 'description' => 'About section title'],
            [
                'key' => 'about_text',
                'value' => __('messages.shop_default_about'),
                'type' => 'string',
                'description' => 'About section text',
            ],
            ['key' => 'about_image_url', 'value' => '', 'type' => 'string', 'description' => 'About section image'],

            // ── Gallery ───────────────────────────────────────────────────────
            ['key' => 'gallery_enabled', 'value' => '0',      'type' => 'boolean', 'description' => 'Photo gallery enabled'],
            ['key' => 'gallery_title',   'value' => 'Galeria', 'type' => 'string',  'description' => 'Gallery section title'],
            ['key' => 'gallery_images',  'value' => '[]',      'type' => 'json',    'description' => 'Gallery images (JSON)'],

            // ── Homepage blocks ───────────────────────────────────────────────
            [
                'key' => 'homepage_blocks',
                'value' => json_encode([
                    [
                        'id' => 'block-welcome',
                        'type' => 'announcement',
                        'enabled' => true,
                        'title' => __('messages.shop_default_newsletter_title'),
                        'content' => __('messages.shop_default_newsletter_text'),
                        'bg_color' => '#ede9fe',
                        'link_url' => '',
                        'link_text' => 'Zobacz katalog',
                    ],
                ]),
                'type' => 'json',
                'description' => 'Homepage blocks',
            ],

            // ── Legal pages ───────────────────────────────────────────────────
            [
                'key' => 'terms_content',
                'type' => 'text',
                'description' => 'Terms and conditions text (HTML)',
                'value' => '<h2>Regulamin sklepu internetowego</h2>
<p><strong>Podmiot prowadzący:</strong> {shop_name}, {shop_address}, e-mail: {shop_email}, tel.: {shop_phone}<br>NIP: {shop_nip}</p>

<h3>§ 1. Postanowienia ogólne</h3>
<p>Niniejszy Regulamin określa zasady składania zamówień za pośrednictwem serwisu internetowego {shop_name} (dalej: „Serwis"). Korzystając z Serwisu, Klient akceptuje warunki Regulaminu.</p>

<h3>§ 2. Składanie zamówień</h3>
<ol>
  <li>Zamówienia można składać przez całą dobę, 7 dni w tygodniu.</li>
  <li>Do złożenia zamówienia niezbędne jest podanie: imienia, nazwiska, adresu wysyłki, numeru telefonu oraz wyboru metody płatności.</li>
  <li>Po złożeniu zamówienia Klient otrzymuje potwierdzenie na podany adres e-mail.</li>
  <li>Sklep zastrzega sobie prawo do odmowy realizacji zamówienia w przypadku podejrzenia błędu lub nadużycia.</li>
</ol>

<h3>§ 3. Ceny i płatności</h3>
<ol>
  <li>Ceny podane w Serwisie są cenami brutto (zawierają podatek VAT) wyrażonymi w złotych polskich (PLN).</li>
  <li>Dostępne metody płatności: przelew bankowy, płatność przy odbiorze, płatność online (jeśli dostępna).</li>
  <li>Koszt wysyłki podawany jest przed finalizacją zamówienia.</li>
</ol>

<h3>§ 4. Wysyłka i odbiór</h3>
<ol>
  <li>Sklep realizuje wysyłkę na wskazany przez Klienta adres za pośrednictwem wybranego przewoźnika.</li>
  <li>Czas realizacji zamówienia jest szacunkowy i zależy od dostępności produktu oraz wybranej metody dostawy.</li>
  <li>Klient może odebrać zamówienie osobiście pod adresem: {shop_address} (jeśli opcja dostępna).</li>
</ol>

<h3>§ 5. Reklamacje i zwroty</h3>
<ol>
  <li>Reklamacje dotyczące zamówień należy zgłaszać mailowo na adres {shop_email} lub telefonicznie pod numer {shop_phone}.</li>
  <li>Konsument ma prawo do zwrotu towaru w ciągu 14 dni od otrzymania przesyłki bez podania przyczyny.</li>
  <li>Sklep rozpatruje reklamacje w ciągu 14 dni roboczych.</li>
</ol>

<h3>§ 6. Ochrona danych osobowych</h3>
<p>Dane osobowe Klientów przetwarzane są zgodnie z Polityką Prywatności dostępną na stronie Serwisu.</p>

<h3>§ 7. Postanowienia końcowe</h3>
<p>W sprawach nieuregulowanych niniejszym Regulaminem zastosowanie mają przepisy Kodeksu Cywilnego oraz ustawy o prawach konsumenta. Regulamin obowiązuje od {year} roku. {shop_name} zastrzega sobie prawo do zmiany Regulaminu z zachowaniem odpowiedniego okresu powiadomienia Klientów.</p>',
            ],
            [
                'key' => 'privacy_content',
                'type' => 'text',
                'description' => 'Privacy policy text (HTML)',
                'value' => '<h2>Polityka Prywatności</h2>
<p>Niniejsza Polityka Prywatności opisuje zasady przetwarzania danych osobowych przez <strong>{shop_name}</strong> z siedzibą pod adresem {shop_address} (dalej: „Administrator").</p>

<h3>1. Administrator danych</h3>
<p>Administratorem Twoich danych osobowych jest {shop_name}, {shop_address}.<br>
Kontakt: {shop_email} | {shop_phone}<br>
NIP: {shop_nip}</p>

<h3>2. Jakie dane zbieramy</h3>
<ul>
  <li><strong>Dane zamówieniowe:</strong> imię, nazwisko, adres wysyłki, numer telefonu, adres e-mail.</li>
  <li><strong>Dane konta:</strong> adres e-mail, hasło (zaszyfrowane), historia zamówień.</li>
  <li><strong>Dane techniczne:</strong> adres IP, informacje o przeglądarce (pliki cookies).</li>
</ul>

<h3>3. Cel i podstawa prawna przetwarzania</h3>
<ul>
  <li><strong>Realizacja zamówień</strong> – art. 6 ust. 1 lit. b RODO (wykonanie umowy).</li>
  <li><strong>Konto użytkownika</strong> – art. 6 ust. 1 lit. b RODO (wykonanie umowy).</li>
  <li><strong>Marketing (za zgodą)</strong> – art. 6 ust. 1 lit. a RODO.</li>
  <li><strong>Obowiązki prawne (np. faktury)</strong> – art. 6 ust. 1 lit. c RODO.</li>
  <li><strong>Uzasadniony interes administratora</strong> – art. 6 ust. 1 lit. f RODO (zapobieganie nadużyciom, statystyki).</li>
</ul>

<h3>4. Okres przechowywania danych</h3>
<p>Dane zamówieniowe przechowywane są przez 5 lat od daty złożenia zamówienia (wymogi podatkowe). Dane konta – do momentu usunięcia konta lub cofnięcia zgody. Dane marketingowe – do odwołania zgody.</p>

<h3>5. Przekazywanie danych</h3>
<p>Dane mogą być przekazywane podmiotom świadczącym usługi na rzecz Administratora (dostawcy hostingu, systemy płatności, firmy kurierskie) wyłącznie w zakresie niezbędnym do realizacji zamówienia.</p>

<h3>6. Twoje prawa</h3>
<p>Przysługuje Ci prawo do: dostępu do danych, ich sprostowania, usunięcia, ograniczenia przetwarzania, przenoszenia danych, wniesienia sprzeciwu. W sprawach związanych z danymi osobowymi skontaktuj się z nami pod adresem {shop_email}. Masz również prawo wniesienia skargi do Prezesa Urzędu Ochrony Danych Osobowych (UODO).</p>

<h3>7. Pliki cookies</h3>
<p>Serwis używa plików cookies w celu zapewnienia prawidłowego działania strony, zapamiętywania preferencji oraz (za Twoją zgodą) celów analitycznych i marketingowych. Możesz zarządzać plikami cookies w ustawieniach swojej przeglądarki.</p>

<h3>8. Zmiany polityki</h3>
<p>Administrator zastrzega sobie prawo do zmiany niniejszej Polityki Prywatności. O istotnych zmianach poinformujemy za pośrednictwem strony Serwisu. Polityka obowiązuje od {year} roku.</p>',
            ],

            // ── Social media ──────────────────────────────────────────────────
            ['key' => 'facebook_url',  'value' => '', 'type' => 'string', 'description' => 'Link do Facebook'],
            ['key' => 'instagram_url', 'value' => '', 'type' => 'string', 'description' => 'Link do Instagram'],
            ['key' => 'tiktok_url',    'value' => '', 'type' => 'string', 'description' => 'Link do TikTok'],

            // ── Analytics & integrations ──────────────────────────────────────
            ['key' => 'google_analytics_id', 'value' => '', 'type' => 'string', 'description' => 'Google Analytics GA4 Measurement ID'],
            ['key' => 'facebook_pixel_id',   'value' => '', 'type' => 'string', 'description' => 'Facebook Pixel ID'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('  ✔ Ustawienia tenanta gotowe (' . count($settings) . ' kluczy).');
    }
}
