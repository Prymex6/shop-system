<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ __('mail.order_shipped_title') }}</title>
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
                    <p style="margin:0 0 4px 0;font-size:24px;">🚚</p>
                    <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:#ffffff;">{{ __('mail.order_on_its_way') }}</p>
                    <p style="margin:0;font-size:13px;color:#ffffff;opacity:.85;">{{ __('mail.order_number_label', ['number' => $order->order_number]) }}</p>
                  </td>
                </tr>
              </table>

              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;">

                    <p style="margin:0 0 16px 0;font-size:15px;color:#374151;">{!! __('mail.greeting_named', ['name' => '<strong>' . e($order->customer_name) . '</strong>']) !!}</p>
                    <p style="margin:0 0 24px 0;font-size:14px;color:#6b7280;line-height:1.7;">
                      {{ __('mail.order_shipped_body') }}
                    </p>

                    @if($order->tracking_number)
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                      <tr>
                        <td style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px;font-size:14px;line-height:1.8;color:#374151;">
                          @if($order->tracking_carrier)
                          <strong>{{ __('mail.label_carrier') }}</strong> {{ $order->tracking_carrier }}<br>
                          @endif
                          <strong>{{ __('mail.label_tracking_number') }}</strong>
                          <span style="font-family:monospace;font-size:15px;color:{{ $primaryColor }};font-weight:700;">{{ $order->tracking_number }}</span>
                        </td>
                      </tr>
                    </table>
                    @endif

                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                      <tr>
                        <td>
                          <a href="{{ $trackingUrl }}" style="display:inline-block;background:{{ $primaryColor }};color:#ffffff;text-decoration:none;padding:13px 28px;border-radius:7px;font-weight:700;font-size:15px;">{{ __('mail.tracking_button') }}</a>
                        </td>
                      </tr>
                    </table>

                    @if(\App\Models\Tenant\Setting::get('shop_phone'))
                    <p style="margin:0;font-size:13px;color:#6b7280;">
                      {{ __('mail.questions_call') }}
                      <a href="tel:{{ \App\Models\Tenant\Setting::get('shop_phone') }}" style="color:{{ $primaryColor }};font-weight:600;text-decoration:none;">{{ \App\Models\Tenant\Setting::get('shop_phone') }}</a>
                    </p>
                    @endif

                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#9ca3af;">
              &copy; {{ date('Y') }} {{ $shopName }} &middot; {{ __('mail.footer_thanks_trust') }}
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
