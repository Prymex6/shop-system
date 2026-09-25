<!DOCTYPE html>
<html lang="pl">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ __('mail.reset_title') }}</title>
</head>

<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#111827;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

          {{-- Header --}}
          <tr>
            <td align="center" style="padding:0 0 24px 0;">
              <p style="margin:0 0 4px 0;font-size:22px;font-weight:700;color:#2563eb;">{{ $shopName }}</p>
              <p style="margin:0;font-size:13px;color:#6b7280;">Panel Pracownika</p>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              {{-- Blue top bar --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:#2563eb;padding:28px 32px;">
                    <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:#ffffff;">{{ __('mail.reset_title') }}</p>
                    <p style="margin:0;font-size:13px;color:#bfdbfe;">{{ __('mail.reset_subtitle') }}</p>
                  </td>
                </tr>
              </table>

              {{-- Body --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;">

                    <p style="margin:0 0 16px 0;font-size:15px;color:#374151;">{{ __('mail.greeting_named', ['name' => $user->name]) }}</p>

                    <p style="margin:0 0 24px 0;font-size:14px;color:#6b7280;line-height:1.7;">
                      {!! __('mail.reset_staff_intro', [
                          'email' => '<strong style="color:#374151;">' . e($user->email) . '</strong>',
                      ]) !!}
                    </p>

                    {{-- Expire notice --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                      <tr>
                        <td style="background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:12px 16px;font-size:13px;color:#713f12;line-height:1.6;">
                          {!! __('mail.reset_link_expires', [
                              'minutes' => '<strong style="color:#713f12;">' . e($expireMinutes) . '</strong>',
                          ]) !!}
                        </td>
                      </tr>
                    </table>

                    {{-- Button --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                      <tr>
                        <td align="center">
                          <a href="{{ $url }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:8px;font-weight:600;font-size:15px;">
                            {{ __('mail.reset_button') }}
                          </a>
                        </td>
                      </tr>
                    </table>

                    <p style="margin:0 0 24px 0;font-size:14px;color:#6b7280;line-height:1.7;">
                      {{ __('mail.reset_ignore') }}
                    </p>

                    {{-- Divider --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                      <tr>
                        <td style="border-top:1px solid #e5e7eb;font-size:0;">&nbsp;</td>
                      </tr>
                    </table>

                    <p style="margin:0;font-size:12px;color:#9ca3af;line-height:1.6;">
                      {{ __('mail.button_fallback') }}<br />
                      <a href="{{ $url }}" style="color:#2563eb;word-break:break-all;">{{ $url }}</a>
                    </p>

                  </td>
                </tr>
              </table>

            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#9ca3af;line-height:1.6;">
              &copy; {{ date('Y') }} {{ $shopName }}. {{ __('mail.footer_automatic') }}
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>

</html>