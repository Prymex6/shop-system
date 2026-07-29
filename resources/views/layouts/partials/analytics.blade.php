@php
    $ga4Id = null;
    try {
        if (tenancy()->initialized && tenancy()->tenant) {
            $ga4Id = \App\Models\Tenant\Setting::get('ga4_measurement_id')
                  ?: \App\Models\Tenant\Setting::get('google_analytics_id');
        }
    } catch (\Throwable) {}
@endphp

@if($ga4Id)
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Id }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '{{ $ga4Id }}');
</script>
@endif
