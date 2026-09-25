<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ __('mail.expiring_title') }}</title>
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
              <p style="margin:0;font-size:13px;color:#6b7280;">{{ __('mail.loyalty_kicker') }}</p>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              {{-- Amber top bar --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:#f59e0b;padding:32px 32px;">
                    <p style="margin:0 0 4px 0;font-size:22px;font-weight:700;color:#ffffff;">{{ __('mail.expiring_heading') }}</p>
                    <p style="margin:0;font-size:14px;color:#ffffff;opacity:.9;">{{ $shopName }}</p>
                  </td>
                </tr>
              </table>

              {{-- Body --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;">

                    <p style="margin:0 0 16px 0;font-size:15px;color:#374151;">{!! __('mail.greeting_named', ['name' => '<strong>' . e($customer->name) . '</strong>']) !!}</p>

                    {{-- Alert box --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                      <tr>
                        <td style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:16px 18px;font-size:15px;color:#374151;">
                          {!! __('mail.expiring_body') !!}
                          <p style="margin:8px 0 4px 0;font-size:36px;font-weight:800;color:#f59e0b;">{{ $expiringPoints }} pkt</p>
                          <p style="margin:0;font-size:13px;color:#92400e;">Wygasa: <strong>{{ $expiresAt }}</strong></p>
                        </td>
                      </tr>
                    </table>

                    <p style="margin:0 0 16px 0;font-size:14px;color:#6b7280;line-height:1.7;">{{ __('mail.expiring_how') }}</p>

                    <p style="margin:0 0 20px 0;font-size:14px;color:#6b7280;line-height:1.7;">{!! __('mail.current_balance', [
                        'balance' => '<strong style="color:' . e($primaryColor) . ';">' . __('mail.points_count', ['count' => $customer->loyalty_points]) . '</strong>',
                    ]) !!}</p>

                    {{-- Button --}}
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td>
                          <a href="{{ url('/moje-konto') }}" style="display:inline-block;background:{{ $primaryColor }};color:#ffffff;text-decoration:none;padding:13px 28px;border-radius:7px;font-weight:700;font-size:15px;">{{ __('mail.expiring_button') }}</a>
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
              &copy; {{ date('Y') }} {{ $shopName }} &middot; {{ __('mail.footer_loyalty') }}
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
