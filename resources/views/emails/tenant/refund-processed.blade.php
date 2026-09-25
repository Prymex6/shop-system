<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Zwrot przetworzony</title>
</head>
@php
  $primaryColor = \App\Models\Tenant\Setting::get('theme_primary_color', '#4f46e5');
  $shopName = \App\Models\Tenant\Setting::get('shop_name', config('app.name'));
  $order = $refund->order;
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
                  <td align="center" style="background:#16a34a;padding:28px 32px;">
                    <p style="margin:0 0 4px 0;font-size:24px;">✅</p>
                    <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:#ffffff;">Zwrot przetworzony</p>
                    <p style="margin:0;font-size:13px;color:#ffffff;opacity:.85;">{{ __('mail.order_number_label', ['number' => $order->order_number ?? '']) }}</p>
                  </td>
                </tr>
              </table>

              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;">

                    <p style="margin:0 0 16px 0;font-size:15px;color:#374151;">{!! __('mail.greeting_named', ['name' => '<strong>' . e($order->customer_name ?? '') . '</strong>']) !!}</p>
                    <p style="margin:0 0 24px 0;font-size:14px;color:#6b7280;line-height:1.7;">
                      {{ __('mail.refund_body') }}
                    </p>

                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                      <tr>
                        <td style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px;font-size:14px;line-height:1.9;color:#374151;">
                          <strong>Kwota zwrotu:</strong>
                          <span style="font-size:18px;font-weight:700;color:#16a34a;">@money($refund->amount, $order->currency ?? 'PLN')</span><br>
                          @if($refund->reason)
                          <strong>{{ __('mail.label_reason') }}</strong> {{ $refund->reason }}<br>
                          @endif
                          <strong>{{ __('mail.label_status') }}</strong> <span style="color:#16a34a;font-weight:600;">{{ __('mail.refund_done') }}</span>
                        </td>
                      </tr>
                    </table>

                    <p style="margin:0;font-size:13px;color:#6b7280;line-height:1.6;">
                      {{ __('mail.refund_timing') }}
                    </p>

                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#9ca3af;">
              &copy; {{ date('Y') }} {{ $shopName }}
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
