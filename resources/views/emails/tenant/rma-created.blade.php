<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Zgłoszenie zwrotu</title>
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
              <p style="margin:0;font-size:13px;color:#6b7280;">Obsługa zwrotów</p>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              {{-- Colored top bar --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:{{ $primaryColor }};padding:28px 32px;">
                    <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:#ffffff;">Zwrot zarejestrowany</p>
                    <p style="margin:0;font-size:13px;color:#ffffff;opacity:.85;">Numer zgłoszenia: {{ $rma->rma_number }}</p>
                  </td>
                </tr>
              </table>

              {{-- Body --}}
              <table width="100%" cellpadding="0" cellspacing="0" style="padding:28px 32px;">
                <tr>
                  <td>
                    <p style="margin:0 0 16px 0;font-size:15px;line-height:1.6;">
                      Cześć, <strong>{{ $rma->customer_name }}</strong>!
                    </p>
                    <p style="margin:0 0 16px 0;font-size:15px;line-height:1.6;">
                      Twoje zgłoszenie zwrotu dla zamówienia <strong>#{{ $order->order_number }}</strong> zostało przyjęte przez nasz system.
                    </p>

                    {{-- RMA Details --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border-radius:8px;padding:16px;margin:0 0 20px 0;">
                      <tr>
                        <td style="padding:4px 0;font-size:14px;color:#6b7280;">Numer zgłoszenia:</td>
                        <td style="padding:4px 0;font-size:14px;font-weight:600;text-align:right;">{{ $rma->rma_number }}</td>
                      </tr>
                      <tr>
                        <td style="padding:4px 0;font-size:14px;color:#6b7280;">Zamówienie:</td>
                        <td style="padding:4px 0;font-size:14px;text-align:right;">#{{ $order->order_number }}</td>
                      </tr>
                      <tr>
                        <td style="padding:4px 0;font-size:14px;color:#6b7280;">Status:</td>
                        <td style="padding:4px 0;font-size:14px;text-align:right;">Oczekuje na rozpatrzenie</td>
                      </tr>
                      <tr>
                        <td style="padding:4px 0;font-size:14px;color:#6b7280;">Powód zwrotu:</td>
                        <td style="padding:4px 0;font-size:14px;text-align:right;">{{ $rma->reason }}</td>
                      </tr>
                    </table>

                    <p style="margin:0 0 16px 0;font-size:14px;line-height:1.6;color:#6b7280;">
                      Nasz zespół rozpatrzy Twoje zgłoszenie w ciągu 2-5 dni roboczych. Otrzymasz wiadomość email z dalszymi instrukcjami.
                    </p>

                    <p style="margin:0;font-size:14px;line-height:1.6;color:#6b7280;">
                      Jeśli masz pytania, skontaktuj się z nami podając numer zgłoszenia: <strong>{{ $rma->rma_number }}</strong>.
                    </p>
                  </td>
                </tr>
              </table>

              {{-- Footer --}}
              <table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e5e7eb;padding:20px 32px;">
                <tr>
                  <td align="center" style="font-size:12px;color:#9ca3af;">
                    © {{ date('Y') }} {{ $shopName }}. Wszystkie prawa zastrzeżone.
                  </td>
                </tr>
              </table>

            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

</body>
</html>
