<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $campaign->subject }}</title>
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
              <p style="margin:0;font-size:13px;color:#6b7280;">{{ __('mail.campaign_kicker') }}</p>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              {{-- Colored top bar --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:{{ $primaryColor }};padding:28px 32px;">
                    <p style="margin:0;font-size:20px;font-weight:700;color:#ffffff;">{{ $shopName }}</p>
                  </td>
                </tr>
              </table>

              {{-- Body --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:28px 32px;font-size:15px;line-height:1.7;color:#374151;">
                    <p style="margin:0 0 16px 0;">@if($recipientName){!! __('mail.greeting_named', ['name' => '<strong>' . e($recipientName) . '</strong>']) !!}@else{{ __('mail.greeting_bare') }}@endif</p>
                    {!! nl2br(e($campaign->content)) !!}
                  </td>
                </tr>
              </table>

            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#9ca3af;line-height:1.6;">
              <p style="margin:0 0 4px 0;">&copy; {{ date('Y') }} {{ $shopName }}</p>
              <p style="margin:0 0 4px 0;font-size:11px;">Otrzymujesz ten email jako klient lub subskrybent newslettera naszego sklepu.</p>
              <p style="margin:0;font-size:11px;">
                <a href="{{ $unsubscribeUrl }}" style="color:#9ca3af;text-decoration:underline;">{{ __('mail.campaign_unsubscribe') }}</a>
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
