<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $reason === 'price_drop' ? 'Cena spadła' : 'Znowu dostępny' }}</title>
</head>
@php
  $primaryColor = \App\Models\Tenant\Setting::get('theme_primary_color', '#4f46e5');
  $shopName = \App\Models\Tenant\Setting::get('shop_name', config('app.name'));
  $productUrl = url('/produkt/' . $product->slug);
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
              <p style="margin:0;font-size:13px;color:#6b7280;">Produkt z Twojej listy życzeń</p>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:{{ $primaryColor }};padding:28px 32px;">
                    @if($reason === 'price_drop')
                      <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:#ffffff;">Cena spadła! 🎉</p>
                      <p style="margin:0;font-size:13px;color:#ffffff;opacity:.85;">Produkt z Twojej listy życzeń jest teraz tańszy.</p>
                    @else
                      <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:#ffffff;">Znowu dostępny! 📦</p>
                      <p style="margin:0;font-size:13px;color:#ffffff;opacity:.85;">Produkt z Twojej listy życzeń wrócił na stan.</p>
                    @endif
                  </td>
                </tr>
              </table>

              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;">

                    <p style="margin:0 0 16px 0;font-size:16px;font-weight:600;color:#1f2937;">{{ $product->name }}</p>

                    @if($reason === 'price_drop')
                      <p style="margin:0 0 20px 0;font-size:14px;color:#6b7280;">
                        Poprzednio: <span style="text-decoration:line-through;">{{ number_format($oldPrice, 2, ',', ' ') }} zł</span>
                        &nbsp;→&nbsp;
                        <span style="color:#16a34a;font-weight:700;font-size:16px;">{{ number_format($product->price, 2, ',', ' ') }} zł</span>
                      </p>
                    @else
                      <p style="margin:0 0 20px 0;font-size:14px;color:#6b7280;">
                        Cena: <span style="font-weight:700;color:#1f2937;">{{ number_format($product->price, 2, ',', ' ') }} zł</span>
                      </p>
                    @endif

                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                      <tr>
                        <td>
                          <a href="{{ $productUrl }}" style="display:inline-block;background:{{ $primaryColor }};color:#ffffff;text-decoration:none;padding:13px 28px;border-radius:7px;font-weight:700;font-size:15px;">Zobacz produkt</a>
                        </td>
                      </tr>
                    </table>

                    <p style="margin:0;font-size:13px;color:#9ca3af;">
                      Otrzymujesz tę wiadomość, ponieważ dodałeś ten produkt do listy życzeń.
                    </p>

                  </td>
                </tr>
              </table>

            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#9ca3af;line-height:1.6;">
              &copy; {{ date('Y') }} {{ $shopName }}
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
