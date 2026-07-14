<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Twój sklep jest gotowy!</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

          {{-- Header --}}
          <tr>
            <td align="center" style="padding:0 0 24px 0;">
              <p style="margin:0 0 4px 0;font-size:22px;font-weight:700;color:#1e40af;">{{ config('app.name') }}</p>
              <p style="margin:0;font-size:13px;color:#6b7280;">System zarządzania sklepem</p>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              {{-- Blue top bar --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:#1e40af;padding:28px 32px;">
                    <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:#ffffff;">Twój sklep jest gotowy!</p>
                    <p style="margin:0;font-size:13px;color:#bfdbfe;">{{ config('app.name') }}</p>
                  </td>
                </tr>
              </table>

              {{-- Body --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;">

                    <p style="margin:0 0 16px 0;font-size:15px;color:#374151;">Cześć <strong>{{ $ownerName }}</strong>,</p>
                    <p style="margin:0 0 20px 0;font-size:14px;color:#6b7280;line-height:1.7;">
                      Konto dla sklepu <strong style="color:#374151;">{{ $shopName }}</strong> zostało utworzone. Możesz już się zalogować i zacząć konfigurować swój panel.
                    </p>

                    {{-- Credentials box --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                      <tr>
                        <td style="background:#ffffff;border:1px solid #d1d5db;border-radius:8px;padding:20px;">
                          <p style="margin:0 0 12px 0;font-size:15px;font-weight:700;color:#111827;">Twoje dane logowania</p>
                          <p style="margin:0 0 8px 0;font-size:14px;color:#374151;"><strong>Login:</strong> {{ $email }}</p>
                          <p style="margin:0 0 8px 0;font-size:14px;color:#374151;"><strong>Hasło:</strong> {{ $password }}</p>
                          <p style="margin:0;font-size:13px;color:#ef4444;">Zmień hasło po pierwszym logowaniu!</p>
                        </td>
                      </tr>
                    </table>

                    {{-- Button --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                      <tr>
                        <td align="center">
                          <a href="{{ $loginUrl }}" style="display:inline-block;background:#1e40af;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:700;font-size:15px;">Przejdź do panelu &rarr;</a>
                        </td>
                      </tr>
                    </table>

                    <p style="margin:0 0 8px 0;font-size:14px;color:#6b7280;line-height:1.7;">Jeśli masz pytania, skontaktuj się z nami odpowiadając na tę wiadomość.</p>
                    <p style="margin:0 0 8px 0;font-size:14px;color:#6b7280;">Powodzenia!</p>
                    <p style="margin:0;font-size:14px;color:#6b7280;"><em>Zespół {{ config('app.name') }}</em></p>

                  </td>
                </tr>
              </table>

            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#9ca3af;line-height:1.6;">
              {{ config('app.name') }} &ndash; System zarządzania sklepem
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
