<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Niski stan magazynowy</title>
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
              <p style="margin:0;font-size:22px;font-weight:700;color:{{ $primaryColor }};">{{ $shopName }}</p>
            </td>
          </tr>

          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:#f59e0b;padding:28px 32px;">
                    <p style="margin:0 0 4px 0;font-size:24px;">⚠️</p>
                    <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:#ffffff;">Niski stan magazynowy</p>
                    <p style="margin:0;font-size:13px;color:#ffffff;opacity:.85;">Wymaga Twojej uwagi</p>
                  </td>
                </tr>
              </table>

              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;">

                    <p style="margin:0 0 24px 0;font-size:14px;color:#6b7280;line-height:1.7;">
                      Stan magazynowy poniższego produktu jest niski i wymaga uzupełnienia.
                    </p>

                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                      <tr>
                        <td style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:16px;font-size:14px;line-height:1.9;color:#374151;">
                          <strong style="font-size:16px;">{{ $product->name }}</strong><br>
                          @if($variant)
                          <span style="color:#6b7280;">Wariant: {{ $variant->label() }}</span><br>
                          <span style="color:#6b7280;">SKU: {{ $variant->sku ?? '—' }}</span><br>
                          @elseif($product->sku)
                          <span style="color:#6b7280;">SKU: {{ $product->sku }}</span><br>
                          @endif
                          <strong>Aktualny stan:</strong>
                          <span style="font-size:20px;font-weight:700;color:#dc2626;">{{ $currentStock }}</span> szt.
                        </td>
                      </tr>
                    </table>

                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td>
                          <a href="{{ url('/panel/magazyn') }}" style="display:inline-block;background:{{ $primaryColor }};color:#ffffff;text-decoration:none;padding:11px 24px;border-radius:7px;font-weight:600;font-size:14px;">Przejdź do magazynu</a>
                        </td>
                      </tr>
                    </table>

                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#9ca3af;">
              &copy; {{ date('Y') }} {{ $shopName }} &middot; Powiadomienie systemowe
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
