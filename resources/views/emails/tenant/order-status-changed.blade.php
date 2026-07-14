<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Aktualizacja zamówienia</title>
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
              <p style="margin:0;font-size:13px;color:#6b7280;">Zamówienia online</p>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              {{-- Colored top bar --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:{{ $primaryColor }};padding:28px 32px;">
                    <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:#ffffff;">{{ $shopName }}</p>
                    <p style="margin:0;font-size:13px;color:#ffffff;opacity:.85;">Aktualizacja zamówienia #{{ $order->order_number }}</p>
                  </td>
                </tr>
              </table>

              {{-- Body --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;">

                    <p style="margin:0 0 16px 0;font-size:15px;color:#374151;">Cześć, <strong>{{ $order->customer_name }}</strong>!</p>

                    @php
                      $cfg = match($newStatus) {
                        'pending'           => ['icon'=>'🕐','title'=>'Zamówienie przyjęte','desc'=>'Twoje zamówienie zostało przyjęte i oczekuje na potwierdzenie.','bg'=>'#f3f4f6','color'=>'#374151'],
                        'awaiting_payment'  => ['icon'=>'💳','title'=>'Oczekiwanie na płatność','desc'=>'Prosimy o finalizację płatności, aby zamówienie zostało przyjęte.','bg'=>'#fef9c3','color'=>'#92400e'],
                        'paid'              => ['icon'=>'✅','title'=>'Zamówienie przyjęte do realizacji!','desc'=>'Płatność potwierdzona. Przystępujemy do realizacji zamówienia.','bg'=>'#dcfce7','color'=>'#15803d'],
                        'completed'         => ['icon'=>'🎉','title'=>'Zrealizowane!','desc'=>'Dziękujemy za zakupy! Twoje zamówienie zostało zrealizowane.','bg'=>'#dcfce7','color'=>'#15803d'],
                        'cancelled'         => ['icon'=>'❌','title'=>'Anulowane','desc'=>'Zamówienie zostało anulowane. Przepraszamy za niedogodności.','bg'=>'#fee2e2','color'=>'#b91c1c'],
                        'refunded'          => ['icon'=>'💰','title'=>'Zwrot środków','desc'=>'Zwrot środków za zamówienie został zainicjowany.','bg'=>'#dbeafe','color'=>'#1d4ed8'],
                        default             => ['icon'=>'📦','title'=>'Status zaktualizowany','desc'=>'Sprawdź szczegóły zamówienia.','bg'=>'#f3f4f6','color'=>'#374151'],
                      };
                    @endphp

                    {{-- Status card --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                      <tr>
                        <td align="center" style="background:{{ $cfg['bg'] }};color:{{ $cfg['color'] }};padding:28px;border-radius:10px;text-align:center;">
                          <p style="margin:0 0 12px 0;font-size:56px;line-height:1;">{{ $cfg['icon'] }}</p>
                          <p style="margin:0 0 8px 0;font-size:22px;font-weight:700;color:{{ $cfg['color'] }};">{{ $cfg['title'] }}</p>
                          @if($cfg['desc'])<p style="margin:0;font-size:15px;color:{{ $cfg['color'] }};opacity:.85;">{{ $cfg['desc'] }}</p>@endif
                        </td>
                      </tr>
                    </table>

                    {{-- Button --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                      <tr>
                        <td align="center">
                          <a href="{{ $trackingUrl ?? (route('tenant.order.tracking', $order->order_number) . '?token=' . \App\Http\Controllers\Tenant\Client\OrderTrackingController::generateToken($order->order_number)) }}" style="display:inline-block;background:{{ $primaryColor }};color:#ffffff;text-decoration:none;padding:13px 28px;border-radius:7px;font-weight:700;font-size:15px;">Śledź zamówienie</a>
                        </td>
                      </tr>
                    </table>

                    @if(\App\Models\Tenant\Setting::get('shop_phone'))
                    <p style="margin:0;font-size:13px;color:#6b7280;text-align:center;">
                      Pytania? Zadzwoń:
                      <a href="tel:{{ \App\Models\Tenant\Setting::get('shop_phone') }}" style="color:{{ $primaryColor }};font-weight:600;text-decoration:none;">{{ \App\Models\Tenant\Setting::get('shop_phone') }}</a>
                    </p>
                    @endif

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
