<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ __('mail.order_confirmed_title') }}</title>
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
                  <td align="center" style="background:{{ $primaryColor }};padding:28px 32px;">
                    <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:#ffffff;">{{ __('mail.order_accepted') }}</p>
                    <p style="margin:0;font-size:13px;color:#ffffff;opacity:.85;">{{ $shopName }}</p>
                  </td>
                </tr>
              </table>

              {{-- Body --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;">

                    <p style="margin:0 0 8px 0;font-size:15px;color:#374151;">{!! __('mail.greeting_named', ['name' => '<strong>' . e($order->customer_name) . '</strong>']) !!}</p>
                    <p style="margin:0 0 16px 0;font-size:14px;color:#6b7280;line-height:1.7;">{{ __('mail.order_confirmed_body') }}</p>

                    <p style="margin:0 0 20px 0;font-size:30px;font-weight:800;color:{{ $primaryColor }};">#{{ $order->order_number }}</p>

                    {{-- Info box --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                      <tr>
                        <td style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px 16px;font-size:14px;line-height:1.7;color:#374151;">
                          @if($order->shippingMethod)
                          <strong>{{ __('mail.label_shipping') }}</strong> {{ $order->shippingMethod->name }}<br>
                          @endif
                          @php $shipAddr = collect([$order->shipping_address['street'] ?? null, trim(($order->shipping_address['postcode'] ?? '') . ' ' . ($order->shipping_address['city'] ?? '')), $order->shipping_address['country'] ?? null])->filter()->implode(', '); @endphp
                          @if($shipAddr !== '')
                          <strong>{{ __('mail.label_shipping_address') }}</strong> {{ $shipAddr }}<br>
                          @endif
                          <strong>{{ __('mail.label_payment_method') }}</strong>
                          @php $pmLabels = ['przelewy24'=>'Przelewy24','payu'=>'PayU','tpay'=>'Tpay','stripe'=>'Stripe','bank_transfer'=>__('mail.payment_bank_transfer'),'cash_on_delivery'=>__('mail.payment_on_delivery')]; @endphp
                          {{ $pmLabels[$order->payment_method] ?? $order->payment_method }}
                        </td>
                      </tr>
                    </table>

                    {{-- Items table --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;font-size:14px;border-collapse:collapse;">
                      <thead>
                        <tr>
                          <th style="background:#f3f4f6;padding:9px 10px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;font-weight:600;">Produkt</th>
                          <th style="background:#f3f4f6;padding:9px 10px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;font-weight:600;">{{ __('mail.label_quantity') }}</th>
                          <th style="background:#f3f4f6;padding:9px 10px;text-align:right;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;font-weight:600;">Cena</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($order->items as $item)
                        <tr>
                          <td style="padding:10px;border-bottom:1px solid #f3f4f6;">
                            {{ $item->name }}
                            @if($item->variant_label ?? $item->variant_name)<br><span style="font-size:12px;color:#6b7280;">{{ $item->variant_label ?? $item->variant_name }}</span>@endif
                          </td>
                          <td style="padding:10px;border-bottom:1px solid #f3f4f6;text-align:center;">{{ $item->quantity }}×</td>
                          <td style="padding:10px;border-bottom:1px solid #f3f4f6;text-align:right;">@money($item->price * $item->quantity, $order->currency ?? 'PLN')</td>
                        </tr>
                        @endforeach
                        @if($order->shipping_cost > 0)
                        <tr>
                          <td colspan="2" style="padding:10px;border-bottom:1px solid #f3f4f6;color:#6b7280;">{{ __('mail.table_shipping') }}</td>
                          <td style="padding:10px;border-bottom:1px solid #f3f4f6;text-align:right;color:#6b7280;">@money($order->shipping_cost, $order->currency ?? 'PLN')</td>
                        </tr>
                        @endif
                        @if($order->discount > 0)
                        <tr>
                          <td colspan="2" style="padding:10px;border-bottom:1px solid #f3f4f6;color:#16a34a;">Rabat</td>
                          <td style="padding:10px;border-bottom:1px solid #f3f4f6;text-align:right;color:#16a34a;">−@money($order->discount, $order->currency ?? 'PLN')</td>
                        </tr>
                        @endif
                        <tr>
                          <td colspan="2" style="padding:14px 10px 10px 10px;font-weight:700;font-size:17px;">RAZEM</td>
                          <td style="padding:14px 10px 10px 10px;font-weight:700;font-size:17px;text-align:right;color:{{ $primaryColor }};">@money($order->total, $order->currency ?? 'PLN')</td>
                        </tr>
                      </tbody>
                    </table>


                    <p style="margin:0 0 12px 0;font-size:14px;color:#374151;">{{ __('mail.tracking_intro') }}</p>

                    {{-- Button --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                      <tr>
                        <td>
                          <a href="{{ $trackingUrl ?? (route('tenant.order.tracking', $order->order_number) . '?token=' . \App\Http\Controllers\Tenant\Client\OrderTrackingController::generateToken($order->order_number)) }}" style="display:inline-block;background:{{ $primaryColor }};color:#ffffff;text-decoration:none;padding:13px 28px;border-radius:7px;font-weight:700;font-size:15px;">{{ __('mail.tracking_button') }}</a>
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

          {{-- Footer --}}
          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#9ca3af;line-height:1.6;">
              &copy; {{ date('Y') }} {{ $shopName }} &middot; {{ __('mail.footer_thanks_trust') }}
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
