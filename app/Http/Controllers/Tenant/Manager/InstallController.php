<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\RolePermission;
use App\Models\Tenant\Setting;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;

class InstallController extends Controller
{
    public function index()
    {
        if (Setting::get('setup_completed', false)) {
            return redirect()->route('tenant.manager.dashboard');
        }

        $existingManager = User::where('role', 'manager')->first();
        $step = session('install_step', 0);

        return Inertia::render('Tenant/Install', [
            'step' => $step,
            'existing_manager_email' => $existingManager?->email,
        ]);
    }

    /** Step 0 — create manager account OR update password of existing manager */
    public function createAccount(Request $request)
    {
        if (Setting::get('setup_completed', false)) {
            return redirect('/');
        }

        $existingManager = User::where('role', 'manager')->first();

        if ($existingManager) {
            // A manager account already exists. This request is unauthenticated,
            // so we must never accept a new password for it directly — that would
            // let anyone who finds this URL before the real owner finishes setup
            // take over the account. Send a real password-reset email instead.
            Password::broker('tenant_users')->sendResetLink(['email' => $existingManager->email]);

            return redirect('/install')->with(
                'info',
                __('messages.install_manager_exists')
            );
        } else {
            // Fresh install — create new manager account
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'manager',
            ]);

            Auth::guard('tenant')->login($user);
            $request->session()->regenerate();
        }

        session(['install_step' => 1]);

        return redirect('/install');
    }

    /** Step 1 — save basic shop info */
    public function saveShop(Request $request)
    {
        $validated = $request->validate([
            'shop_name' => ['required', 'string', 'max:255'],
            'shop_phone' => ['nullable', 'string', 'max:30'],
            'shop_email' => ['nullable', 'email', 'max:255'],
            'shop_nip' => ['nullable', 'string', 'max:20'],
            // Required: the seeded legal pages (terms, privacy policy)
            // substitute this into a {shop_address} token — without it those
            // pages publish with an empty "Adres: " field, missing the seller
            // identification required by art. 12 of the consumer rights act.
            'shop_address' => ['required', 'string', 'max:500'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value ?? '');
        }

        return $this->finishSetup();
    }

    /** Step 2 — save branding (logo, description) */
    public function saveBranding(Request $request)
    {
        if (Setting::get('setup_completed', false)) {
            return redirect('/');
        }

        $validated = $request->validate([
            'logo_url' => ['nullable', 'string', 'max:500'],
            'shop_description' => ['nullable', 'string', 'max:2000'],
        ]);

        if (!empty($validated['logo_url'])) {
            Setting::set('logo_url', $validated['logo_url']);
        }
        if (!empty($validated['shop_description'])) {
            Setting::set('shop_description', $validated['shop_description']);
            Setting::set('about_text', $validated['shop_description']);
        }

        return $this->finishSetup();
    }

    /** Complete setup (skip branding) */
    public function complete(Request $request)
    {
        return $this->finishSetup();
    }

    private function finishSetup()
    {
        Setting::set('setup_completed', true);
        session()->forget('install_step');
        $this->seedDefaultRolePermissions();
        $this->seedDefaultContent();

        return redirect()->route('tenant.manager.dashboard')
            ->with('success', __('messages.install_finished', ['app' => config('app.name')]));
    }

    private function seedDefaultContent(): void
    {
        $name = htmlspecialchars(Setting::get('shop_name', 'Sklep'), ENT_QUOTES);

        if (!Setting::has('logo_url')) {
            Setting::set('logo_url', '/images/logo.png');
        }
        if (!Setting::has('favicon_url')) {
            Setting::set('favicon_url', '/images/favicon.png');
        }
        if (!Setting::has('hero_image_url')) {
            Setting::set('hero_image_url', '/images/hero.png');
        }

        if (!Setting::has('shop_description')) {
            Setting::set('shop_description', __('messages.shop_default_description', ['name' => $name]));
        }

        if (!Setting::has('about_text')) {
            Setting::set('about_text', __('messages.shop_default_description', ['name' => $name]));
        }

        if (!Setting::has('terms_content')) {
            Setting::set('terms_content', <<<'HTML'
<h1>Regulamin sklepu internetowego</h1>
<p style="color:#6b7280;font-size:0.9em;">Wersja obowiązująca od 1 stycznia 2026 r.</p>

<p><strong>Sprzedawca:</strong> {shop_owner_name}<br>
Nazwa handlowa: {shop_name}<br>
Adres: {shop_address}<br>
E-mail: {shop_email} &nbsp;|&nbsp; Tel.: {shop_phone}<br>
NIP: {shop_nip}</p>

<hr>

<h2>§ 1. Definicje</h2>
<ol>
  <li><strong>Sklep</strong> – sklep internetowy prowadzony przez Sprzedawcę, umożliwiający zakup produktów.</li>
  <li><strong>Sprzedawca</strong> – {shop_owner_name}, dane wskazane powyżej.</li>
  <li><strong>Klient</strong> – pełnoletnia osoba fizyczna posiadająca pełną zdolność do czynności prawnych, osoba prawna lub jednostka organizacyjna nieposiadająca osobowości prawnej, dokonująca zakupów w Sklepie.</li>
  <li><strong>Konsument</strong> – Klient będący osobą fizyczną dokonujący zakupów w celach niezwiązanych bezpośrednio z działalnością gospodarczą lub zawodową (art. 22¹ Kodeksu cywilnego).</li>
  <li><strong>Zamówienie</strong> – oświadczenie woli Klienta złożone za pośrednictwem Sklepu, stanowiące ofertę zawarcia umowy sprzedaży.</li>
  <li><strong>Umowa</strong> – umowa sprzedaży zawierana na odległość pomiędzy Klientem a Sprzedawcą za pośrednictwem Sklepu.</li>
  <li><strong>Regulamin</strong> – niniejszy dokument.</li>
</ol>

<h2>§ 2. Postanowienia ogólne</h2>
<ol>
  <li>Niniejszy Regulamin określa zasady i warunki korzystania ze Sklepu, składania Zamówień, zawierania Umów oraz tryb postępowania reklamacyjnego.</li>
  <li>Regulamin dostępny jest w Sklepie w formie umożliwiającej jego pobranie, utrwalenie i wydrukowanie.</li>
  <li>Korzystanie ze Sklepu jest równoznaczne z zaakceptowaniem postanowień niniejszego Regulaminu.</li>
  <li>Sprzedawca świadczy usługi drogą elektroniczną w rozumieniu ustawy z dnia 18 lipca 2002 r. o świadczeniu usług drogą elektroniczną (Dz.U. 2002 nr 144 poz. 1204 ze zm.).</li>
</ol>

<h2>§ 3. Wymagania techniczne</h2>
<ol>
  <li>Korzystanie ze Sklepu wymaga posiadania urządzenia z dostępem do sieci Internet oraz aktualnej przeglądarki internetowej obsługującej JavaScript i pliki cookies.</li>
  <li>Sprzedawca nie ponosi odpowiedzialności za problemy techniczne wynikające z konfiguracji urządzenia Klienta lub jakości połączenia internetowego.</li>
</ol>

<h2>§ 4. Rejestracja i konto użytkownika</h2>
<ol>
  <li>Klient może dokonywać zakupów bez rejestracji lub założyć Konto, podając dane niezbędne do jego utworzenia.</li>
  <li>Klient zobowiązany jest do podania danych prawdziwych, aktualnych i kompletnych.</li>
  <li>Klient może w każdej chwili usunąć swoje Konto. Dane osobowe są anonimizowane zgodnie z Polityką Prywatności, z wyjątkiem danych niezbędnych do realizacji obowiązków prawnych.</li>
</ol>

<h2>§ 5. Składanie zamówień i zawarcie umowy</h2>
<ol>
  <li>Zamówienia można składać za pośrednictwem Sklepu przez całą dobę, 7 dni w tygodniu.</li>
  <li>Złożenie Zamówienia stanowi ofertę Klienta. Umowa zostaje zawarta z chwilą przesłania przez Sprzedawcę potwierdzenia przyjęcia Zamówienia do realizacji.</li>
  <li>Potwierdzenie przyjęcia Zamówienia wysyłane jest na adres e-mail Klienta wskazany podczas składania Zamówienia.</li>
  <li>Sprzedawca zastrzega sobie prawo do anulowania Zamówienia w przypadku: niedostępności Produktu w magazynie, niemożności weryfikacji danych Klienta lub awarii systemu płatności. Pobrane środki zostaną niezwłocznie zwrócone.</li>
</ol>

<h2>§ 6. Ceny i płatności</h2>
<ol>
  <li>Wszystkie ceny podane w Sklepie są cenami brutto wyrażonymi w złotych polskich (PLN) i zawierają podatek VAT w obowiązującej stawce.</li>
  <li>Koszt dostawy podawany jest odrębnie przed finalizacją Zamówienia.</li>
  <li>Sprzedawca oferuje płatność elektroniczną (BLIK, karta płatnicza, przelew online), przelew bankowy oraz — jeśli aktywna — płatność za pobraniem.</li>
  <li>Sprzedawca zastrzega sobie prawo do zmiany cen Produktów oraz wprowadzania i odwoływania promocji. Zmiany cen nie dotyczą Zamówień już przyjętych do realizacji.</li>
</ol>

<h2>§ 7. Realizacja i dostawa</h2>
<ol>
  <li>Czas realizacji Zamówienia wynosi co do zasady 1–3 dni robocze, o ile przy danym Produkcie nie wskazano inaczej.</li>
  <li>Zamówienia złożone w dni robocze do godz. 14:00 przekazywane są do wysyłki tego samego dnia.</li>
  <li>Dostawa realizowana jest za pośrednictwem firm kurierskich lub do punktów odbioru (paczkomaty). Dostępne opcje i koszty prezentowane są w Koszyku.</li>
  <li>Sprzedawca nie ponosi odpowiedzialności za opóźnienia wynikające z działania przewoźnika, siły wyższej lub błędnego adresu podanego przez Klienta.</li>
</ol>

<h2>§ 8. Prawo do odstąpienia od umowy (Konsumenci)</h2>
<ol>
  <li>Konsument ma prawo do odstąpienia od Umowy zawartej na odległość bez podania przyczyny w terminie <strong>14 dni</strong> od daty otrzymania Produktu (art. 27 ustawy o prawach konsumenta).</li>
  <li>Aby skorzystać z prawa do odstąpienia, Konsument powinien poinformować Sprzedawcę drogą elektroniczną na adres <strong>{shop_email}</strong>, podając numer Zamówienia.</li>
  <li>Konsument zobowiązany jest zwrócić Produkt w terminie 14 dni od dnia odstąpienia. Bezpośrednie koszty zwrotu ponosi Konsument.</li>
  <li>Sprzedawca zwraca wszystkie płatności (w tym najtańszy koszt dostawy) w terminie 14 dni od otrzymania oświadczenia o odstąpieniu.</li>
  <li>Prawo odstąpienia nie przysługuje m.in. w odniesieniu do Produktów: wyprodukowanych według specyfikacji Konsumenta lub wyraźnie spersonalizowanych; z zapieczętowanym opakowaniem higienicznym, jeżeli opakowanie zostało otwarte po dostarczeniu.</li>
</ol>

<h2>§ 9. Reklamacje i gwarancja</h2>
<ol>
  <li>Sprzedawca ponosi odpowiedzialność za wady fizyczne i prawne Produktów na zasadach rękojmi (art. 556 i nast. Kodeksu cywilnego) przez okres 2 lat od daty wydania Produktu Klientowi.</li>
  <li>Reklamacje należy składać elektronicznie na adres <strong>{shop_email}</strong> lub pisemnie na adres: <strong>{shop_address}</strong>.</li>
  <li>Reklamacja powinna zawierać: imię i nazwisko, numer Zamówienia, opis wady oraz żądanie (naprawa, wymiana, obniżenie ceny, odstąpienie od umowy).</li>
  <li>Sprzedawca rozpatruje reklamacje w terminie 14 dni kalendarzowych i informuje Klienta o wyniku drogą elektroniczną.</li>
  <li>Konsumentowi przysługuje prawo do skorzystania z platformy ODR: <a href="https://ec.europa.eu/consumers/odr" target="_blank">ec.europa.eu/consumers/odr</a>.</li>
</ol>

<h2>§ 10. Ochrona danych osobowych</h2>
<p>Dane osobowe Klientów przetwarzane są zgodnie z RODO (Rozporządzenie PE i Rady (UE) 2016/679) oraz obowiązującymi przepisami krajowymi. Szczegółowe informacje zawiera Polityka Prywatności dostępna w Sklepie.</p>

<h2>§ 11. Postanowienia końcowe</h2>
<ol>
  <li>W sprawach nieuregulowanych niniejszym Regulaminem zastosowanie mają przepisy prawa polskiego, w szczególności Kodeksu cywilnego, ustawy o świadczeniu usług drogą elektroniczną oraz ustawy o prawach konsumenta.</li>
  <li>Sprzedawca zastrzega sobie prawo do zmiany Regulaminu z co najmniej 14-dniowym wyprzedzeniem dla Klientów posiadających Konto.</li>
  <li>Niniejszy Regulamin obowiązuje od dnia 1 stycznia 2026 r.</li>
</ol>
HTML, 'text');
        }

        if (!Setting::has('privacy_content')) {
            Setting::set('privacy_content', <<<'HTML'
<h1>Polityka Prywatności i Ochrony Danych Osobowych</h1>
<p style="color:#6b7280;font-size:0.9em;">Wersja obowiązująca od 1 stycznia 2026 r.</p>

<p>Niniejsza Polityka Prywatności sporządzona jest zgodnie z wymogami Rozporządzenia PE i Rady (UE) 2016/679 (<strong>RODO</strong>) oraz ustawy z dnia 10 maja 2018 r. o ochronie danych osobowych.</p>

<hr>

<h2>1. Administrator danych osobowych</h2>
<p><strong>{shop_owner_name}</strong><br>
Nazwa handlowa: {shop_name}<br>
Adres: {shop_address}<br>
E-mail: {shop_email} | Tel.: {shop_phone}<br>
NIP: {shop_nip}</p>

<h2>2. Kategorie przetwarzanych danych</h2>
<table style="width:100%;border-collapse:collapse;font-size:0.9em;">
  <thead><tr style="background:#f3f4f6;">
    <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Kategoria</th>
    <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Zakres danych</th>
  </tr></thead>
  <tbody>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;">Dane identyfikacyjne</td><td style="padding:8px;border:1px solid #e5e7eb;">imię i nazwisko</td></tr>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;">Dane kontaktowe</td><td style="padding:8px;border:1px solid #e5e7eb;">adres e-mail, numer telefonu</td></tr>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;">Dane adresowe</td><td style="padding:8px;border:1px solid #e5e7eb;">adres dostawy, opcjonalnie adres do faktury</td></tr>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;">Dane transakcyjne</td><td style="padding:8px;border:1px solid #e5e7eb;">historia zamówień, wartość, metoda płatności, numer przesyłki</td></tr>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;">Dane konta</td><td style="padding:8px;border:1px solid #e5e7eb;">e-mail, hasło (hash bcrypt), data rejestracji</td></tr>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;">Dane techniczne</td><td style="padding:8px;border:1px solid #e5e7eb;">adres IP, typ przeglądarki, pliki cookies</td></tr>
  </tbody>
</table>

<h2>3. Cele i podstawy prawne przetwarzania</h2>
<table style="width:100%;border-collapse:collapse;font-size:0.9em;">
  <thead><tr style="background:#f3f4f6;">
    <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Cel przetwarzania</th>
    <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Podstawa prawna (RODO)</th>
  </tr></thead>
  <tbody>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;">Realizacja zamówień i dostawa</td><td style="padding:8px;border:1px solid #e5e7eb;">art. 6 ust. 1 lit. b – wykonanie umowy</td></tr>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;">Obsługa reklamacji i zwrotów</td><td style="padding:8px;border:1px solid #e5e7eb;">art. 6 ust. 1 lit. b i f – umowa / uzasadniony interes</td></tr>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;">Obowiązki podatkowe i rachunkowe</td><td style="padding:8px;border:1px solid #e5e7eb;">art. 6 ust. 1 lit. c – obowiązek prawny</td></tr>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;">Marketing własnych produktów</td><td style="padding:8px;border:1px solid #e5e7eb;">art. 6 ust. 1 lit. f – uzasadniony interes</td></tr>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;">Newsletter (za zgodą)</td><td style="padding:8px;border:1px solid #e5e7eb;">art. 6 ust. 1 lit. a – zgoda</td></tr>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;">Bezpieczeństwo i zapobieganie nadużyciom</td><td style="padding:8px;border:1px solid #e5e7eb;">art. 6 ust. 1 lit. f – uzasadniony interes</td></tr>
  </tbody>
</table>

<h2>4. Okres przechowywania danych</h2>
<ul>
  <li><strong>Dane zamówieniowe</strong> – 5 lat od końca roku złożenia zamówienia (wymogi podatkowe).</li>
  <li><strong>Dane konta</strong> – do momentu usunięcia konta; po usunięciu dane są anonimizowane.</li>
  <li><strong>Dane na podstawie zgody</strong> – do momentu cofnięcia zgody.</li>
  <li><strong>Dane do celów roszczeń</strong> – do upływu okresu przedawnienia (3 lub 6 lat).</li>
</ul>

<h2>5. Odbiorcy danych</h2>
<p>Dane udostępniane są wyłącznie podmiotom współpracującym przy realizacji zamówień: dostawcom IT, operatorom płatności, firmom kurierskim, biurom rachunkowym. Administrator nie sprzedaje danych osobowych.</p>

<h2>6. Prawa osób, których dane dotyczą</h2>
<ul>
  <li><strong>Prawo dostępu</strong> (art. 15 RODO) – prawo do informacji i kopii danych.</li>
  <li><strong>Prawo do sprostowania</strong> (art. 16 RODO) – prawo do poprawienia nieprawidłowych danych.</li>
  <li><strong>Prawo do usunięcia</strong> (art. 17 RODO) – „prawo do bycia zapomnianym".</li>
  <li><strong>Prawo do ograniczenia przetwarzania</strong> (art. 18 RODO).</li>
  <li><strong>Prawo do przenoszenia danych</strong> (art. 20 RODO).</li>
  <li><strong>Prawo do sprzeciwu</strong> (art. 21 RODO) – wobec przetwarzania na podstawie uzasadnionego interesu.</li>
  <li><strong>Prawo do skargi</strong> – do Prezesa UODO, ul. Stawki 2, 00-193 Warszawa.</li>
</ul>
<p>Kontakt w sprawach danych: <strong>{shop_email}</strong>. Odpowiedź w ciągu 30 dni.</p>

<h2>7. Pliki cookies</h2>
<ul>
  <li><strong>Niezbędne</strong> – sesja, koszyk, CSRF – nie wymagają zgody.</li>
  <li><strong>Funkcjonalne</strong> – zapamiętują preferencje (waluta, język).</li>
  <li><strong>Analityczne</strong> – analiza ruchu (Google Analytics) – wymagają zgody.</li>
  <li><strong>Marketingowe</strong> – spersonalizowane reklamy – wymagają zgody.</li>
</ul>

<h2>8. Bezpieczeństwo</h2>
<p>Sklep stosuje szyfrowanie HTTPS/TLS, hashowanie haseł (bcrypt) oraz kontrolę dostępu. Dane kart płatniczych nie są przechowywane przez Sklep — obsługiwane są wyłącznie przez certyfikowanych operatorów płatności.</p>

<p style="color:#6b7280;font-size:0.9em;">Polityka obowiązuje od 1 stycznia 2026 r.</p>
HTML, 'text');
        }

        if (!Setting::has('shipping_content')) {
            Setting::set('shipping_content', <<<'HTML'
<h1>Dostawa i płatności</h1>
<p style="color:#6b7280;font-size:0.9em;">Aktualne informacje o metodach i kosztach dostawy oraz dostępnych formach płatności.</p>

<hr>

<h2>1. Czas realizacji zamówienia</h2>
<table style="width:100%;border-collapse:collapse;font-size:0.9em;">
  <thead><tr style="background:#f3f4f6;">
    <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Moment złożenia zamówienia</th>
    <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Nadanie przesyłki</th>
  </tr></thead>
  <tbody>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;">Dzień roboczy, do godz. 14:00</td><td style="padding:8px;border:1px solid #e5e7eb;">Ten sam dzień</td></tr>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;">Dzień roboczy, po godz. 14:00</td><td style="padding:8px;border:1px solid #e5e7eb;">Następny dzień roboczy</td></tr>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;">Weekend lub dzień wolny</td><td style="padding:8px;border:1px solid #e5e7eb;">Pierwszy kolejny dzień roboczy</td></tr>
  </tbody>
</table>

<h2>2. Metody dostawy</h2>
<ul>
  <li><strong>Kurier (DPD / DHL / InPost Kurier)</strong> – dostawa pod wskazany adres, 1–2 dni robocze od nadania.</li>
  <li><strong>Paczkomat InPost</strong> – odbiór w wybranym paczkomacie, 1–2 dni robocze od nadania.</li>
  <li><strong>Odbiór osobisty</strong> – pod adresem: {shop_address}, w godzinach uzgodnionych ze Sprzedawcą.</li>
</ul>
<p>Aktualne koszty dostawy wyświetlane są w koszyku. Przy zamówieniach powyżej określonego progu dostawa jest bezpłatna.</p>

<h2>3. Śledzenie przesyłki</h2>
<p>Po nadaniu paczki Klient otrzymuje numer przesyłki i link do śledzenia na adres e-mail. Status zamówienia dostępny jest też w zakładce <em>Moje zamówienia</em>.</p>

<h2>4. Formy płatności</h2>
<table style="width:100%;border-collapse:collapse;font-size:0.9em;">
  <thead><tr style="background:#f3f4f6;">
    <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Metoda</th>
    <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Opis</th>
  </tr></thead>
  <tbody>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;"><strong>BLIK</strong></td><td style="padding:8px;border:1px solid #e5e7eb;">Szybka płatność kodem BLIK — środki rezerwowane natychmiast.</td></tr>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;"><strong>Karta płatnicza</strong></td><td style="padding:8px;border:1px solid #e5e7eb;">Visa / Mastercard z autoryzacją 3D Secure. Dane karty nie są przechowywane przez Sklep.</td></tr>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;"><strong>Przelew online</strong></td><td style="padding:8px;border:1px solid #e5e7eb;">Pay By Link — szybki przelew przez stronę banku.</td></tr>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;"><strong>Przelew tradycyjny</strong></td><td style="padding:8px;border:1px solid #e5e7eb;">Realizacja zamówienia po zaksięgowaniu wpłaty (1–2 dni robocze).</td></tr>
    <tr><td style="padding:8px;border:1px solid #e5e7eb;"><strong>Za pobraniem</strong></td><td style="padding:8px;border:1px solid #e5e7eb;">Gotówką lub kartą przy odbiorze od kuriera (jeśli opcja jest dostępna).</td></tr>
  </tbody>
</table>

<h2>5. Faktury VAT</h2>
<p>Fakturę VAT wystawiamy na życzenie — zaznacz odpowiednią opcję podczas składania zamówienia i podaj dane firmowe (NIP, nazwa, adres). Faktura wysyłana jest w formie elektronicznej (PDF).</p>

<h2>6. Kontakt</h2>
<p>📧 {shop_email} &nbsp; 📞 {shop_phone}</p>
HTML, 'text');
        }

        if (!Setting::has('returns_content')) {
            Setting::set('returns_content', <<<'HTML'
<h1>Zwroty i reklamacje</h1>
<p style="color:#6b7280;font-size:0.9em;">Twoje prawa jako Konsumenta są dla nas priorytetem.</p>

<hr>

<h2>1. Prawo do odstąpienia od umowy (zwrot towaru)</h2>
<p>Zgodnie z art. 27 ustawy o prawach konsumenta, Konsument ma prawo odstąpić od umowy zawartej na odległość <strong>bez podania przyczyny</strong> w terminie <strong>14 dni</strong> od daty otrzymania towaru.</p>

<h3>Jak złożyć zwrot — krok po kroku:</h3>
<ol>
  <li><strong>Poinformuj nas</strong> — wyślij e-mail na adres <strong>{shop_email}</strong> lub zadzwoń pod numer <strong>{shop_phone}</strong>, podając numer zamówienia.</li>
  <li><strong>Zapakuj towar</strong> — ostrożnie, najlepiej w oryginalne opakowanie, dołącz dowód zakupu.</li>
  <li><strong>Wyślij paczkę</strong> — na adres: <strong>{shop_address}</strong>. Koszty odesłania ponosi Konsument.</li>
  <li><strong>Zwrot środków</strong> — po otrzymaniu towaru Sprzedawca zwraca płatności w terminie 14 dni.</li>
</ol>

<h3>Ważne informacje:</h3>
<ul>
  <li>Towar nie może nosić śladów użytkowania wykraczającego poza normalne sprawdzenie.</li>
  <li>Prawo zwrotu <strong>nie przysługuje</strong> w przypadku: produktów na indywidualne zamówienie; produktów z zapieczętowanym opakowaniem higienicznym, jeżeli zostało ono otwarte.</li>
  <li>Zwrot środków realizowany jest tą samą metodą płatności, chyba że Klient wyraził zgodę na inny sposób.</li>
</ul>

<h2>2. Reklamacja z tytułu rękojmi</h2>
<p>Na podstawie art. 556 i nast. Kodeksu cywilnego Sprzedawca odpowiada za wady fizyczne i prawne towaru przez <strong>2 lata</strong> od daty jego wydania.</p>

<h3>Kiedy przysługuje reklamacja?</h3>
<ul>
  <li>Produkt jest niezgodny z opisem lub zdjęciami w Sklepie.</li>
  <li>Produkt ma wadę fizyczną (uszkodzenie, nieprawidłowe działanie).</li>
  <li>Produkt jest niekompletny (brakuje elementów zestawu).</li>
  <li>Produkt był uszkodzony w transporcie (zalecamy protokół szkody przy kurierze).</li>
</ul>

<h3>Jak złożyć reklamację:</h3>
<p>Elektronicznie: <strong>{shop_email}</strong> | Pisemnie: <strong>{shop_address}</strong></p>
<p>Reklamacja powinna zawierać: imię i nazwisko, numer zamówienia, opis wady (mile widziane zdjęcia) oraz żądanie (naprawa / wymiana / obniżenie ceny / odstąpienie od umowy).</p>
<p>Sprzedawca rozpatruje reklamację w terminie <strong>14 dni kalendarzowych</strong>.</p>

<h2>3. Pozasądowe rozwiązywanie sporów</h2>
<ul>
  <li>Mediacja przez Wojewódzki Inspektorat Inspekcji Handlowej.</li>
  <li>Platforma ODR: <a href="https://ec.europa.eu/consumers/odr" target="_blank">ec.europa.eu/consumers/odr</a></li>
  <li>Więcej informacji: <a href="https://www.uokik.gov.pl" target="_blank">uokik.gov.pl</a></li>
</ul>

<h2>4. Kontakt</h2>
<p>📧 {shop_email} &nbsp; 📞 {shop_phone} &nbsp; 📮 {shop_address}</p>
HTML, 'text');
        }

        if (!Setting::has('faq_content')) {
            Setting::set('faq_content', <<<'HTML'
<h1>Najczęściej zadawane pytania (FAQ)</h1>
<p style="color:#6b7280;font-size:0.9em;">Odpowiedzi na najczęstsze pytania naszych Klientów.</p>

<hr>

<h2>Zamówienia</h2>

<h3>Jak złożyć zamówienie?</h3>
<p>Dodaj produkty do koszyka, przejdź do kasy, wypełnij dane dostawy i wybierz formę płatności. Po potwierdzeniu otrzymasz e-mail z podsumowaniem.</p>

<h3>Czy mogę kupić bez rejestracji?</h3>
<p>Tak — zakupy jako gość są w pełni obsługiwane. Rejestracja jest opcjonalna, ale daje dostęp do historii zamówień i programu lojalnościowego.</p>

<h3>Jak sprawdzić status zamówienia?</h3>
<p>W zakładce <em>Moje zamówienia</em> po zalogowaniu lub korzystając z linku do śledzenia przesyłki z e-maila potwierdzającego wysyłkę.</p>

<h3>Czy mogę zmienić lub anulować zamówienie?</h3>
<p>Zmiana lub anulowanie jest możliwa wyłącznie przed przekazaniem zamówienia do wysyłki. Skontaktuj się jak najszybciej: <strong>{shop_email}</strong> lub <strong>{shop_phone}</strong>.</p>

<h2>Płatności</h2>

<h3>Jakie formy płatności są dostępne?</h3>
<p>BLIK, karta płatnicza (Visa, Mastercard), przelew online (Pay By Link), przelew tradycyjny oraz — opcjonalnie — płatność za pobraniem. Szczegóły w zakładce <strong>Dostawa i płatności</strong>.</p>

<h3>Czy moje dane płatnicze są bezpieczne?</h3>
<p>Tak. Transakcje realizowane są przez certyfikowanych operatorów płatności (Przelewy24 / PayU / Tpay). Sklep nie przechowuje danych kart. Połączenie szyfrowane protokołem TLS.</p>

<h3>Czy mogę otrzymać fakturę VAT?</h3>
<p>Tak. Zaznacz opcję „Chcę fakturę VAT" podczas składania zamówienia i podaj dane firmowe (NIP, nazwa, adres). Faktura PDF wysyłana jest elektronicznie.</p>

<h2>Dostawa</h2>

<h3>Kiedy zostanie wysłane zamówienie?</h3>
<p>Zamówienia złożone w dni robocze do godz. 14:00 wysyłamy tego samego dnia. W pozostałych przypadkach — następnego dnia roboczego.</p>

<h3>Ile kosztuje dostawa?</h3>
<p>Koszty wyświetlane są w koszyku podczas składania zamówienia. Przy zamówieniach powyżej progu dostawa jest bezpłatna — próg widoczny na stronie głównej.</p>

<h3>Co zrobić, gdy paczka jest uszkodzona?</h3>
<p>Sporządź protokół szkody w obecności kuriera, a następnie skontaktuj się z nami: <strong>{shop_email}</strong>.</p>

<h2>Zwroty i reklamacje</h2>

<h3>Ile mam czasu na zwrot?</h3>
<p>Jako Konsument masz <strong>14 dni</strong> od daty otrzymania towaru na odstąpienie od umowy bez podania przyczyny. Szczegóły w zakładce <strong>Zwroty i reklamacje</strong>.</p>

<h3>Jak długo trwa rozpatrzenie reklamacji?</h3>
<p>Reklamacje rozpatrujemy w terminie <strong>14 dni kalendarzowych</strong> od otrzymania zgłoszenia.</p>

<h3>Kiedy otrzymam zwrot pieniędzy?</h3>
<p>W terminie <strong>14 dni</strong> od otrzymania zwróconego towaru lub oświadczenia o odstąpieniu. Środki zwracane są tą samą metodą, której użyto przy zakupie.</p>

<h2>Konto i program lojalnościowy</h2>

<h3>Zapomniałem/-am hasła — co zrobić?</h3>
<p>Kliknij „Zaloguj się" → „Zapomniałem/-am hasła" i podaj e-mail powiązany z kontem. Wyślemy link do resetowania hasła.</p>

<h3>Jak działa program lojalnościowy?</h3>
<p>Za każdą wydaną złotówkę otrzymujesz punkty wymieniane na zniżki przy kolejnych zakupach. Szczegóły po zalogowaniu w zakładce <em>Program lojalnościowy</em>.</p>

<h2>Kontakt</h2>
<p>Nie znalazłeś/-aś odpowiedzi? Napisz do nas:<br>
📧 <strong>{shop_email}</strong> &nbsp; 📞 <strong>{shop_phone}</strong></p>
HTML, 'text');
        }
    }

    private function seedDefaultRolePermissions(): void
    {
        foreach (RolePermissionsController::DEFAULTS as $role => $permissions) {
            foreach ($permissions as $permission) {
                RolePermission::firstOrCreate(['role' => $role, 'permission' => $permission]);
            }
        }
    }
}
