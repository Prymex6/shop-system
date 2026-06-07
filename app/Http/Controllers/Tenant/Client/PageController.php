<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Page;
use App\Models\Tenant\Setting;
use Inertia\Inertia;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)->where('status', 'published')->firstOrFail();

        return Inertia::render('Tenant/Client/Page', [
            'page' => $page,
        ]);
    }

    public function terms()
    {
        return Inertia::render('Tenant/Client/Pages/Terms', [
            'content' => $this->resolve(Setting::get('terms_content', '')),
        ]);
    }

    public function privacy()
    {
        return Inertia::render('Tenant/Client/Pages/Privacy', [
            'content' => $this->resolve(Setting::get('privacy_content', '')),
        ]);
    }

    public function shipping()
    {
        return Inertia::render('Tenant/Client/Pages/Shipping', [
            'content' => $this->resolve(Setting::get('shipping_content', '')),
        ]);
    }

    public function returns()
    {
        return Inertia::render('Tenant/Client/Pages/Returns', [
            'content' => $this->resolve(Setting::get('returns_content', '')),
        ]);
    }

    public function faq()
    {
        return Inertia::render('Tenant/Client/Pages/Faq', [
            'content' => $this->resolve(Setting::get('faq_content', '')),
        ]);
    }

    private function resolve(?string $content): string
    {
        $tokens = [
            '{shop_name}' => Setting::get('shop_name', ''),
            '{shop_owner_name}' => Setting::get('shop_owner_name', Setting::get('shop_name', '')),
            '{shop_address}' => Setting::get('shop_address', ''),
            '{shop_phone}' => Setting::get('shop_phone', ''),
            '{shop_email}' => Setting::get('shop_email', ''),
            '{shop_nip}' => Setting::get('shop_nip', ''),
            '{year}' => date('Y'),
        ];

        return str_replace(array_keys($tokens), array_values($tokens), $content ?? '');
    }
}
