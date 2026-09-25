<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Category;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomepageBuilderController extends Controller
{
    // What every available block looks like before anyone edits it
    private const DEFAULT_BLOCKS = [
        ['id' => 'hero', 'type' => 'hero', 'enabled' => true, 'settings' => [
            'heading' => '', 'subheading' => 'Odkryj nasze produkty', 'cta_label' => 'Przeglądaj sklep', 'height' => 'md', 'slides' => '',
        ]],
        ['id' => 'trust_badges', 'type' => 'trust_badges', 'enabled' => true, 'settings' => [
            'badges' => [
                ['icon' => 'fa-solid fa-truck-fast',    'label' => 'Darmowa dostawa',      'sub' => 'od 199 zł',          'color' => '#059669', 'bg' => '#ecfdf5'],
                ['icon' => 'fa-solid fa-shield-halved', 'label' => 'Bezpieczne płatności', 'sub' => 'SSL + 3D Secure',    'color' => '#2563eb', 'bg' => '#eff6ff'],
                ['icon' => 'fa-solid fa-rotate-left',   'label' => '30 dni na zwrot',      'sub' => 'bez podawania powodu', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
                ['icon' => 'fa-solid fa-headset',       'label' => 'Wsparcie klienta',     'sub' => 'pon–pt, 9–17',       'color' => '#d97706', 'bg' => '#fffbeb'],
            ],
        ]],
        ['id' => 'categories', 'type' => 'categories', 'enabled' => true, 'settings' => [
            'heading' => 'Kategorie', 'subheading' => 'Przeglądaj asortyment', 'limit' => '8',
        ]],
        ['id' => 'featured_products', 'type' => 'featured_products', 'enabled' => true, 'settings' => [
            'heading' => 'Polecane produkty', 'subheading' => 'Specjalnie dla Ciebie',
            'source' => 'featured', 'category_id' => '', 'limit' => '4', 'show_link' => true,
        ]],
        ['id' => 'bestsellers', 'type' => 'bestsellers', 'enabled' => true, 'settings' => [
            'heading' => 'Bestsellery', 'subheading' => 'Najpopularniejsze',
            'source' => 'bestsellers', 'category_id' => '', 'limit' => '4', 'show_link' => true,
        ]],
        ['id' => 'new_arrivals', 'type' => 'new_arrivals', 'enabled' => true, 'settings' => [
            'heading' => 'Nowości', 'subheading' => 'Właśnie dodane',
            'source' => 'newest', 'category_id' => '', 'limit' => '4', 'show_link' => true,
        ]],
        ['id' => 'banner', 'type' => 'banner', 'enabled' => false, 'settings' => [
            'heading' => '', 'text' => '', 'eyebrow' => '', 'btn_label' => '', 'btn_url' => '', 'bg_color' => '#1e40af', 'image_url' => '', 'countdown_end' => '',
        ]],
        ['id' => 'about', 'type' => 'about', 'enabled' => false, 'settings' => [
            'heading' => 'O nas', 'text' => '', 'image_url' => '', 'layout' => 'image_right',
        ]],
        ['id' => 'gallery', 'type' => 'gallery', 'enabled' => false, 'settings' => [
            'heading' => 'Galeria', 'images' => '',
        ]],
        ['id' => 'reviews', 'type' => 'reviews', 'enabled' => false, 'settings' => [
            'heading' => 'Opinie klientów', 'subheading' => 'Co mówią nasi klienci', 'limit' => '6',
        ]],
        ['id' => 'newsletter', 'type' => 'newsletter', 'enabled' => false, 'settings' => [
            'heading' => 'Bądź na bieżąco', 'text' => 'Zapisz się i otrzymuj oferty jako pierwszy.',
        ]],
        ['id' => 'testimonials', 'type' => 'testimonials', 'enabled' => false, 'settings' => [
            'heading' => 'Opinie klientów', 'subheading' => 'Co mówią o nas klienci',
            'item1_author' => '', 'item1_text' => '', 'item1_rating' => '5',
            'item2_author' => '', 'item2_text' => '', 'item2_rating' => '5',
            'item3_author' => '', 'item3_text' => '', 'item3_rating' => '5',
        ]],
        ['id' => 'faq', 'type' => 'faq', 'enabled' => false, 'settings' => [
            'heading' => 'Najczęściej zadawane pytania', 'subheading' => 'FAQ',
            'q1' => '', 'a1' => '', 'q2' => '', 'a2' => '', 'q3' => '', 'a3' => '', 'q4' => '', 'a4' => '',
        ]],
        ['id' => 'guarantee_badges', 'type' => 'trust_badges', 'enabled' => false, 'settings' => [
            'badges' => [
                ['icon' => 'fa-solid fa-truck-fast',    'label' => 'Szybka wysyłka',       'sub' => '1-2 dni robocze',       'color' => '#059669', 'bg' => '#ecfdf5'],
                ['icon' => 'fa-solid fa-rotate-left',   'label' => '30 dni na zwrot',      'sub' => 'bez podawania powodu',  'color' => '#2563eb', 'bg' => '#eff6ff'],
                ['icon' => 'fa-solid fa-lock',          'label' => 'Bezpieczne zakupy',    'sub' => 'szyfrowane płatności',  'color' => '#7c3aed', 'bg' => '#f5f3ff'],
                ['icon' => 'fa-solid fa-headset',       'label' => 'Obsługa klienta',      'sub' => 'pon–pt, 9–17',          'color' => '#d97706', 'bg' => '#fffbeb'],
            ],
        ]],
    ];

    public function index()
    {
        $saved = Setting::get('homepage_blocks');
        $blocks = $saved ? (is_string($saved) ? json_decode($saved, true) : $saved) : null;

        // Merge saved blocks with defaults (add new block types if missing)
        if ($blocks) {
            $byId = collect($blocks)->keyBy('id');
            $merged = collect(self::DEFAULT_BLOCKS)->map(function ($default) use ($byId) {
                if ($byId->has($default['id'])) {
                    $saved = $byId[$default['id']];

                    return array_merge($default, $saved, [
                        'settings' => array_merge($default['settings'], $saved['settings'] ?? []),
                    ]);
                }

                return $default;
            })->values()->toArray();
        } else {
            $merged = self::DEFAULT_BLOCKS;
        }

        $categories = Category::active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Tenant/Manager/HomepageBuilder/Index', [
            'blocks' => $merged,
            'categories' => $categories,
        ]);
    }

    public function save(Request $request)
    {
        $request->validate([
            'blocks' => ['required', 'array'],
            'blocks.*.id' => ['required', 'string', 'max:64'],
            'blocks.*.type' => ['required', 'string', 'max:64'],
            'blocks.*.enabled' => ['boolean'],
            'blocks.*.title' => ['nullable', 'string', 'max:255'],
            'blocks.*.settings' => ['nullable', 'array'],
        ]);

        // blocks.*.settings.* values are either flat strings (headings, texts, URLs)
        // or a nested array (trust_badges' `badges` list of icon/label/color objects).
        // A flat "settings.* must be string" rule used to reject the nested case
        // outright, so saving a trust_badges block from the builder UI always failed
        // validation — this walks both shapes instead of assuming one.
        foreach ($request->input('blocks', []) as $bi => $block) {
            foreach (($block['settings'] ?? []) as $key => $value) {
                if (is_array($value)) {
                    $request->validate([
                        "blocks.$bi.settings.$key.*.icon" => ['nullable', 'string', 'max:255'],
                        "blocks.$bi.settings.$key.*.label" => ['nullable', 'string', 'max:255'],
                        "blocks.$bi.settings.$key.*.sub" => ['nullable', 'string', 'max:255'],
                        "blocks.$bi.settings.$key.*.color" => ['nullable', 'string', 'max:32'],
                        "blocks.$bi.settings.$key.*.bg" => ['nullable', 'string', 'max:32'],
                    ]);
                } else {
                    $request->validate([
                        "blocks.$bi.settings.$key" => ['nullable', 'string', 'max:2000'],
                    ]);
                }
            }
        }

        Setting::set('homepage_blocks', json_encode($request->input('blocks')));

        return back()->with('success', __('messages.homepage_saved'));
    }
}
