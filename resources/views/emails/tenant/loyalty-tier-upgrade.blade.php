<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ __('mail.tier_upgrade_title') }}</title>
</head>
@php
  $primaryColor = \App\Models\Tenant\Setting::get('theme_primary_color', '#4f46e5');
  $shopName = \App\Models\Tenant\Setting::get('shop_name', config('app.name'));
  $tierColor = $tierConfig['color'] ?? $primaryColor;
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
              <p style="margin:0;font-size:13px;color:#6b7280;">{{ __('mail.loyalty_kicker') }}</p>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              {{-- Colored top bar --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:{{ $tierColor }};padding:40px 32px;">
                    <p style="margin:0 0 6px 0;font-size:26px;font-weight:800;color:#ffffff;">Gratulacje, {{ $customer->name }}!</p>
                    <p style="margin:0;font-size:15px;color:#ffffff;opacity:.9;">{{ $shopName }}</p>
                  </td>
                </tr>
              </table>

              {{-- Body --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;text-align:center;">

                    <p style="margin:0 0 16px 0;font-size:15px;color:#374151;line-height:1.7;">{{ __('mail.tier_upgrade_body') }}</p>

                    {{-- Tier badge --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                      <tr>
                        <td align="center">
                          <span style="display:inline-block;background:{{ $tierColor }};color:#ffffff;padding:12px 32px;border-radius:999px;font-size:22px;font-weight:800;letter-spacing:1px;">{{ $tierConfig['name'] ?? $newTier }}</span>
                        </td>
                      </tr>
                    </table>

                    <p style="margin:0 0 20px 0;font-size:15px;color:#374151;line-height:1.7;">{{ __('mail.tier_upgrade_enjoy') }}</p>

                    {{-- Perks box --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                      <tr>
                        <td style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:18px 20px;text-align:left;font-size:14px;line-height:1.9;color:#374151;">
                          @if(!empty($tierConfig['multiplier']) && $tierConfig['multiplier'] > 1)
                          <div style="margin-bottom:4px;">&#10024; <strong style="color:{{ $tierColor }};">{{ __('mail.tier_multiplier') }}</strong> &times;{{ number_format($tierConfig['multiplier'], 2) }}</div>
                          @endif
                          @if(!empty($tierConfig['monthly_bonus']) && $tierConfig['monthly_bonus'] > 0)
                          <div style="margin-bottom:4px;">🎁 <strong style="color:{{ $tierColor }};">{{ __('mail.tier_monthly_bonus') }}</strong> {{ __('mail.points_count', ['count' => $tierConfig['monthly_bonus']]) }}</div>
                          @endif
                          @if(!empty($tierConfig['delivery_bonus']) && $tierConfig['delivery_bonus'] >= 999)
                          <div style="margin-bottom:4px;">🚚 <strong style="color:{{ $tierColor }};">{{ __('mail.tier_free_delivery') }}</strong> {{ __('mail.tier_free_delivery_note') }}</div>
                          @elseif(!empty($tierConfig['delivery_bonus']) && $tierConfig['delivery_bonus'] > 0)
                          <div style="margin-bottom:4px;">🚚 <strong style="color:{{ $tierColor }};">{{ __('mail.tier_delivery_discount') }}</strong> {{ $tierConfig['delivery_bonus'] }} PLN</div>
                          @endif
                        </td>
                      </tr>
                    </table>

                    <p style="margin:0 0 20px 0;font-size:13px;color:#6b7280;">{!! __('mail.tier_points_now', [
                        'balance' => '<strong style="color:' . e($tierColor) . ';">' . __('mail.points_short', ['count' => $customer->loyalty_points]) . '</strong>',
                    ]) !!}</p>

                    {{-- Button --}}
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td align="center">
                          <a href="{{ url('/moje-konto') }}" style="display:inline-block;background:{{ $primaryColor }};color:#ffffff;text-decoration:none;padding:13px 28px;border-radius:7px;font-weight:700;font-size:15px;">{{ __('mail.tier_button') }}</a>
                        </td>
                      </tr>
                    </table>

                  </td>
                </tr>
              </table>

            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#9ca3af;line-height:1.6;">
              &copy; {{ date('Y') }} {{ $shopName }} &middot; {{ __('mail.footer_thanks_loyalty') }}
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
