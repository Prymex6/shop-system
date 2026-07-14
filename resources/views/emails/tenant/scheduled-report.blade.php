<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Raport tygodniowy</title>
</head>
@php
  $primaryColor = \App\Models\Tenant\Setting::get('theme_primary_color', '#4f46e5');
  $shopName = \App\Models\Tenant\Setting::get('shop_name', config('app.name'));
@endphp
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

          {{-- Header --}}
          <tr>
            <td align="center" style="padding:0 0 24px 0;">
              <p style="margin:0 0 4px 0;font-size:22px;font-weight:700;color:{{ $primaryColor }};">{{ $shopName }}</p>
              <p style="margin:0;font-size:13px;color:#6b7280;">Raport tygodniowy</p>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              {{-- Top bar --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:{{ $primaryColor }};padding:24px 32px;">
                    <p style="margin:0 0 4px 0;font-size:18px;font-weight:700;color:#ffffff;">Raport {{ $period }}</p>
                    <p style="margin:0;font-size:12px;color:#ffffff;opacity:.85;">{{ $dateFrom }} – {{ $dateTo }}</p>
                  </td>
                </tr>
              </table>

              {{-- Stats --}}
              <table width="100%" cellpadding="0" cellspacing="0" style="padding:32px;">
                <tr>
                  <td>

                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                      <tr>
                        <td style="width:50%;padding:12px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;text-align:center;">
                          <p style="margin:0 0 4px 0;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;">Zamówienia</p>
                          <p style="margin:0;font-size:28px;font-weight:800;color:{{ $primaryColor }};">{{ $stats['orders_count'] ?? 0 }}</p>
                        </td>
                        <td style="width:16px;"></td>
                        <td style="width:50%;padding:12px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;text-align:center;">
                          <p style="margin:0 0 4px 0;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;">Przychód</p>
                          <p style="margin:0;font-size:28px;font-weight:800;color:#16a34a;">{{ number_format($stats['revenue'] ?? 0, 2, ',', ' ') }} zł</p>
                        </td>
                      </tr>
                    </table>

                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                      <tr>
                        <td style="width:50%;padding:12px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;text-align:center;">
                          <p style="margin:0 0 4px 0;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;">Nowi klienci</p>
                          <p style="margin:0;font-size:28px;font-weight:800;color:#374151;">{{ $stats['new_customers'] ?? 0 }}</p>
                        </td>
                        <td style="width:16px;"></td>
                        <td style="width:50%;padding:12px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;text-align:center;">
                          <p style="margin:0 0 4px 0;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;">Śr. wartość</p>
                          <p style="margin:0;font-size:28px;font-weight:800;color:#374151;">{{ number_format($stats['avg_order_value'] ?? 0, 2, ',', ' ') }} zł</p>
                        </td>
                      </tr>
                    </table>

                    <p style="margin:0;font-size:13px;color:#9ca3af;text-align:center;">Raport wygenerowany automatycznie przez system {{ $shopName }}</p>

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
