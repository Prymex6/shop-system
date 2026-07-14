<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pliki do pobrania</title>
</head>
@php
  $primaryColor = \App\Models\Tenant\Setting::get('theme_primary_color', '#4f46e5');
  $shopName = \App\Models\Tenant\Setting::get('shop_name', config('app.name'));
@endphp
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

          <tr>
            <td align="center" style="padding:0 0 24px 0;">
              <p style="margin:0 0 4px 0;font-size:22px;font-weight:700;color:{{ $primaryColor }};">{{ $shopName }}</p>
            </td>
          </tr>

          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:{{ $primaryColor }};padding:28px 32px;">
                    <p style="margin:0 0 4px 0;font-size:24px;">📥</p>
                    <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:#ffffff;">Twoje pliki są gotowe!</p>
                    <p style="margin:0;font-size:13px;color:#ffffff;opacity:.85;">Zamówienie #{{ $order->order_number }}</p>
                  </td>
                </tr>
              </table>

              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;">

                    <p style="margin:0 0 16px 0;font-size:15px;color:#374151;">Cześć, <strong>{{ $order->customer_name }}</strong>!</p>
                    <p style="margin:0 0 24px 0;font-size:14px;color:#6b7280;line-height:1.7;">
                      Twoja płatność została potwierdzona. Poniżej znajdziesz linki do pobrania zakupionych plików.
                    </p>

                    @foreach($downloadLinks as $link)
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
                      <tr>
                        <td style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px 16px;">
                          <p style="margin:0 0 4px 0;font-size:14px;font-weight:600;color:#111827;">
                            {{ $link->product->name ?? 'Plik' }}
                            @if($link->file)
                            <span style="font-size:12px;color:#6b7280;font-weight:400;">({{ $link->file->name }})</span>
                            @endif
                          </p>
                          @if($link->expires_at)
                          <p style="margin:0 0 8px 0;font-size:12px;color:#9ca3af;">Wygasa: {{ $link->expires_at->format('d.m.Y H:i') }}</p>
                          @endif
                          @if($link->download_limit)
                          <p style="margin:0 0 8px 0;font-size:12px;color:#9ca3af;">Limit pobrań: {{ $link->download_limit }}</p>
                          @endif
                          <a href="{{ url('/pobieranie/' . $link->token) }}" style="display:inline-block;background:{{ $primaryColor }};color:#ffffff;text-decoration:none;padding:9px 20px;border-radius:6px;font-weight:600;font-size:13px;">Pobierz plik</a>
                        </td>
                      </tr>
                    </table>
                    @endforeach

                    <p style="margin:24px 0 0 0;font-size:13px;color:#6b7280;line-height:1.6;">
                      Wszystkie pobrane pliki znajdziesz również na swoim
                      <a href="{{ url('/moje-konto/pobrane') }}" style="color:{{ $primaryColor }};text-decoration:none;font-weight:600;">koncie klienta</a>.
                    </p>

                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#9ca3af;">
              &copy; {{ date('Y') }} {{ $shopName }} &middot; Dziękujemy za zakup!
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
