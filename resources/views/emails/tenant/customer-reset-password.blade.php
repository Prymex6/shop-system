<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Resetowanie hasła</title>
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

          {{-- Header --}}
          <tr>
            <td align="center" style="padding:0 0 24px 0;">
              <p style="margin:0 0 4px 0;font-size:22px;font-weight:700;color:{{ $primaryColor }};">{{ $shopName }}</p>
              <p style="margin:0;font-size:13px;color:#6b7280;">Konto klienta</p>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              {{-- Colored top bar --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:{{ $primaryColor }};padding:28px 32px;">
                    <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:#ffffff;">Resetowanie hasła</p>
                    <p style="margin:0;font-size:13px;color:#ffffff;opacity:.85;">Otrzymaliśmy prośbę o zmianę hasła do Twojego konta</p>
                  </td>
                </tr>
              </table>

              {{-- Body --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;">

                    <p style="margin:0 0 16px 0;font-size:15px;color:#374151;">Cześć, <strong>{{ $customer->name }}</strong>!</p>

                    <p style="margin:0 0 24px 0;font-size:14px;color:#6b7280;line-height:1.7;">
                      Ktoś (prawdopodobnie Ty) poprosił o zresetowanie hasła do konta powiązanego z adresem
                      <strong style="color:#374151;">{{ $customer->email }}</strong>.
                      Kliknij poniższy przycisk, aby ustawić nowe hasło.
                    </p>

                    {{-- Expire notice --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                      <tr>
                        <td style="background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:12px 16px;font-size:13px;color:#713f12;line-height:1.6;">
                          Link jest ważny przez <strong>{{ $expireMinutes }} minut</strong>. Po tym czasie konieczne będzie ponowne wysłanie prośby o reset.
                        </td>
                      </tr>
                    </table>

                    {{-- Button --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                      <tr>
                        <td align="center">
                          <a href="{{ $url }}" style="display:inline-block;background:{{ $primaryColor }};color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:8px;font-weight:700;font-size:15px;">
                            Ustaw nowe hasło
                          </a>
                        </td>
                      </tr>
                    </table>

                    <p style="margin:0 0 24px 0;font-size:14px;color:#6b7280;line-height:1.7;">
                      Jeśli nie prosiłeś o zmianę hasła, zignoruj tę wiadomość. Twoje hasło pozostanie bez zmian.
                    </p>

                    {{-- Divider --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                      <tr>
                        <td style="border-top:1px solid #e5e7eb;font-size:0;">&nbsp;</td>
                      </tr>
                    </table>

                    <p style="margin:0;font-size:12px;color:#9ca3af;line-height:1.6;">
                      Jeśli przycisk nie działa, skopiuj i wklej poniższy link do przeglądarki:<br />
                      <a href="{{ $url }}" style="color:{{ $primaryColor }};word-break:break-all;">{{ $url }}</a>
                    </p>

                  </td>
                </tr>
              </table>

            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#9ca3af;line-height:1.6;">
              &copy; {{ date('Y') }} {{ $shopName }}. Wiadomość wysłana automatycznie &mdash; nie odpowiadaj na ten e-mail.
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
