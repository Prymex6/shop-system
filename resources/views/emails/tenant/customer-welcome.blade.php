<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Witamy!</title>
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
              <p style="margin:0;font-size:13px;color:#6b7280;">{{ __('mail.welcome_kicker') }}</p>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              {{-- Colored top bar --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:{{ $primaryColor }};padding:32px 32px;">
                    <p style="margin:0 0 6px 0;font-size:24px;font-weight:700;color:#ffffff;">{{ $shopName }}</p>
                    <p style="margin:0;font-size:14px;color:#ffffff;opacity:.9;">{{ __('mail.welcome_subtitle') }}</p>
                  </td>
                </tr>
              </table>

              {{-- Body --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;">

                    <p style="margin:0 0 16px 0;font-size:18px;font-weight:700;color:#111827;">{{ __('mail.greeting_named', ['name' => $customerName]) }}</p>
                    <p style="margin:0 0 16px 0;font-size:15px;color:#374151;line-height:1.6;">{{ __('mail.welcome_thanks') }}</p>
                    <p style="margin:0 0 24px 0;font-size:15px;color:#374151;line-height:1.6;">{{ __('mail.welcome_what_next') }}</p>

                    {{-- Button --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                      <tr>
                        <td align="center">
                          <a href="{{ url('/') }}" style="display:inline-block;background:{{ $primaryColor }};color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:6px;font-weight:700;font-size:15px;">{{ __('mail.welcome_button') }}</a>
                        </td>
                      </tr>
                    </table>

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
