<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ __('mail.cart_waiting_title') }}</title>
</head>
@php
  $primaryColor = \App\Models\Tenant\Setting::get('theme_primary_color', '#4f46e5');
  $shopName = \App\Models\Tenant\Setting::get('shop_name', config('app.name'));
  $cartUrl = url('/koszyk');
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
              <p style="margin:0;font-size:13px;color:#6b7280;">{{ __('mail.cart_waiting_kicker') }}</p>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              {{-- Colored top bar --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:{{ $primaryColor }};padding:28px 32px;">
                    <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:#ffffff;">{{ __('mail.cart_forgot_heading') }}</p>
                    <p style="margin:0;font-size:13px;color:#ffffff;opacity:.85;">{{ __('mail.cart_forgot_sub') }}</p>
                  </td>
                </tr>
              </table>

              {{-- Body --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;">

                    <p style="margin:0 0 16px 0;font-size:14px;color:#6b7280;line-height:1.7;">
                      {{ __('mail.cart_body') }}
                    </p>

                    {{-- Cart items --}}
                    @if(!empty($cartItems))
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;font-size:14px;border-collapse:collapse;">
                      <thead>
                        <tr>
                          <th style="background:#f3f4f6;padding:9px 10px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;font-weight:600;">Produkt</th>
                          <th style="background:#f3f4f6;padding:9px 10px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;font-weight:600;">{{ __('mail.label_quantity') }}</th>
                          <th style="background:#f3f4f6;padding:9px 10px;text-align:right;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;font-weight:600;">Cena</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($cartItems as $item)
                        <tr>
                          <td style="padding:10px;border-bottom:1px solid #f3f4f6;">
                            {{ $item['name'] ?? 'Produkt' }}
                            @if(!empty($item['variant_label']))<br><span style="font-size:12px;color:#6b7280;">{{ $item['variant_label'] }}</span>@endif
                          </td>
                          <td style="padding:10px;border-bottom:1px solid #f3f4f6;text-align:center;">{{ $item['quantity'] ?? 1 }}×</td>
                          <td style="padding:10px;border-bottom:1px solid #f3f4f6;text-align:right;">@money(($item['price'] ?? 0) * ($item['quantity'] ?? 1), $currency ?? 'PLN')</td>
                        </tr>
                        @endforeach
                      </tbody>
                    </table>
                    @endif

                    {{-- CTA Button --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                      <tr>
                        <td>
                          <a href="{{ $cartUrl }}" style="display:inline-block;background:{{ $primaryColor }};color:#ffffff;text-decoration:none;padding:13px 28px;border-radius:7px;font-weight:700;font-size:15px;">{{ __('mail.cart_button') }}</a>
                        </td>
                      </tr>
                    </table>

                    <p style="margin:0;font-size:13px;color:#9ca3af;">
                      {{ __('mail.cart_ignore') }}
                    </p>

                  </td>
                </tr>
              </table>

            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#9ca3af;line-height:1.6;">
              &copy; {{ date('Y') }} {{ $shopName }} &middot; {{ __('mail.footer_buy_later') }}
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
