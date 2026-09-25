<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.licence_page_title', ['shop' => $tenant->name ?? __('messages.shop_fallback_name')]) }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            color: #1e293b;
        }
        .card {
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 4px 32px rgba(0,0,0,.08);
            max-width: 480px;
            width: 100%;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .icon { font-size: 3.5rem; margin-bottom: 1.25rem; }
        h1 { font-size: 1.375rem; font-weight: 700; color: #0f172a; margin-bottom: .5rem; }
        p  { font-size: .9375rem; color: #64748b; line-height: 1.6; margin-bottom: 1.25rem; }
        .badge {
            display: inline-block;
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            border-radius: 9999px;
            font-size: .75rem;
            font-weight: 600;
            padding: .25rem .875rem;
            margin-bottom: 1.5rem;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .badge.suspended {
            background: #fff7ed;
            color: #c2410c;
            border-color: #fed7aa;
        }
        .name { font-size: 1.75rem; margin-bottom: .25rem; }
        .divider { border: none; border-top: 1px solid #f1f5f9; margin: 1.5rem 0; }
        .footer { font-size: .8125rem; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:3rem;height:3rem;color:#94a3b8;">
                <path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z"/>
                <path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z"/>
            </svg>
        </div>
        <p class="name">{{ $tenant->name ?? 'Sklep' }}</p>

        @if (!empty($suspended))
            <div class="badge suspended">Zawieszona</div>
            <h1>{{ __('messages.licence_heading') }}</h1>
            <p>{!! __('messages.licence_suspended_body') !!}</p>
        @else
            <div class="badge">{{ __('messages.licence_expired_badge') }}</div>
            <h1>{{ __('messages.licence_heading') }}</h1>
            <p>{!! __('messages.licence_expired_body') !!}</p>
        @endif

        <hr class="divider">
        <p class="footer">{{ __('messages.licence_owner_note') }}</p>
    </div>
</body>
</html>
