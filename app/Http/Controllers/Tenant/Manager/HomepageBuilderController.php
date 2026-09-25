<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Category;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomepageBuilderController extends Controller
{
    /**
     * What every available block looks like before anyone edits it.
     *
     * A method rather than a constant because the headings are read by
     * whoever is shopping, and a constant cannot call __().
     *
     * @return list<array<string, mixed>>
     */
    private static function defaultBlocks(): array
    {
        return [
            ['id' => 'hero', 'type' => 'hero', 'enabled' => true, 'settings' => [
                'heading' => '', 'subheading' => __('messages.home_hero_subheading'), 'cta_label' => __('messages.home_hero_cta'), 'height' => 'md', 'slides' => '',
            ]],
            ['id' => 'trust_badges', 'type' => 'trust_badges', 'enabled' => true, 'settings' => [
                'badges' => [
                    ['icon' => 'fa-solid fa-truck-fast',    'label' => __('messages.home_badge_free_delivery'),   'sub' => __('messages.home_badge_free_delivery_sub'), 'color' => '#059669', 'bg' => '#ecfdf5'],
                    ['icon' => 'fa-solid fa-shield-halved', 'label' => __('messages.home_badge_secure_payments'), 'sub' => 'SSL + 3D Secure',                           'color' => '#2563eb', 'bg' => '#eff6ff'],
                    ['icon' => 'fa-solid fa-rotate-left',   'label' => __('messages.home_badge_returns'),         'sub' => __('messages.home_badge_returns_sub'),       'color' => '#7c3aed', 'bg' => '#f5f3ff'],
                    ['icon' => 'fa-solid fa-headset',       'label' => __('messages.home_badge_support'),         'sub' => __('messages.home_badge_hours'),             'color' => '#d97706', 'bg' => '#fffbeb'],
                ],
            ]],
            ['id' => 'categories', 'type' => 'categories', 'enabled' => true, 'settings' => [
                'heading' => __('messages.home_categories'), 'subheading' => __('messages.home_categories_sub'), 'limit' => '8',
            ]],
            ['id' => 'featured_products', 'type' => 'featured_products', 'enabled' => true, 'settings' => [
                'heading' => __('messages.home_featured'), 'subheading' => __('messages.home_featured_sub'),
                'source' => 'featured', 'category_id' => '', 'limit' => '4', 'show_link' => true,
            ]],
            ['id' => 'bestsellers', 'type' => 'bestsellers', 'enabled' => true, 'settings' => [
                'heading' => __('messages.home_bestsellers'), 'subheading' => __('messages.home_bestsellers_sub'),
                'source' => 'bestsellers', 'category_id' => '', 'limit' => '4', 'show_link' => true,
            ]],
            ['id' => 'new_arrivals', 'type' => 'new_arrivals', 'enabled' => true, 'settings' => [
                'heading' => __('messages.home_new_arrivals'), 'subheading' => __('messages.home_new_arrivals_sub'),
                'source' => 'newest', 'category_id' => '', 'limit' => '4', 'show_link' => true,
            ]],
            ['id' => 'banner', 'type' => 'banner', 'enabled' => false, 'settings' => [
                'heading' => '', 'text' => '', 'eyebrow' => '', 'btn_label' => '', 'btn_url' => '', 'bg_color' => '#1e40af', 'image_url' => '', 'countdown_end' => '',
            ]],
            ['id' => 'about', 'type' => 'about', 'enabled' => false, 'settings' => [
                'heading' => __('messages.home_about'), 'text' => '', 'image_url' => '', 'layout' => 'image_right',
            ]],
            ['id' => 'gallery', 'type' => 'gallery', 'enabled' => false, 'settings' => [
                'heading' => __('messages.home_gallery'), 'images' => '',
            ]],
            ['id' => 'reviews', 'type' => 'reviews', 'enabled' => false, 'settings' => [
                'heading' => __('messages.home_reviews'), 'subheading' => __('messages.home_reviews_sub'), 'limit' => '6',
            ]],
            ['id' => 'newsletter', 'type' => 'newsletter', 'enabled' => false, 'settings' => [
                'heading' => __('messages.home_newsletter'), 'text' => __('messages.home_newsletter_text'),
            ]],
            ['id' => 'testimonials', 'type' => 'testimonials', 'enabled' => false, 'settings' => [
                'heading' => __('messages.home_reviews'), 'subheading' => __('messages.home_testimonials_sub'),
                'item1_author' => '', 'item1_text' => '', 'item1_rating' => '5',
                'item2_author' => '', 'item2_text' => '', 'item2_rating' => '5',
                'item3_author' => '', 'item3_text' => '', 'item3_rating' => '5',
            ]],
            ['id' => 'faq', 'type' => 'faq', 'enabled' => false, 'settings' => [
                'heading' => __('messages.home_faq'), 'subheading' => 'FAQ',
                'q1' => '', 'a1' => '', 'q2' => '', 'a2' => '', 'q3' => '', 'a3' => '', 'q4' => '', 'a4' => '',
            ]],
            ['id' => 'guarantee_badges', 'type' => 'trust_badges', 'enabled' => false, 'settings' => [
                'badges' => [
                    ['icon' => 'fa-solid fa-truck-fast',    'label' => __('messages.home_badge_fast_shipping'),   'sub' => __('messages.home_badge_fast_shipping_sub'), 'color' => '#059669', 'bg' => '#ecfdf5'],
                    ['icon' => 'fa-solid fa-rotate-left',   'label' => __('messages.home_badge_returns'),         'sub' => __('messages.home_badge_returns_sub'),       'color' => '#2563eb', 'bg' => '#eff6ff'],
                    ['icon' => 'fa-solid fa-lock',          'label' => __('messages.home_badge_safe_shopping'),   'sub' => __('messages.home_badge_safe_shopping_sub'), 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
                    ['icon' => 'fa-solid fa-headset',       'label' => __('messages.home_badge_customer_service'), 'sub' => __('messages.home_badge_hours'),            'color' => '#d97706', 'bg' => '#fffbeb'],
                ],
            ]],
        ];
    }

    public function index()
    {
        $saved = Setting::get('homepage_blocks');
        $blocks = $saved ? (is_string($saved) ? json_decode($saved, true) : $saved) : null;

        // Merge saved blocks with defaults (add new block types if missing)
        if ($blocks) {
            $byId = collect($blocks)->keyBy('id');
            $merged = collect(self::defaultBlocks())->map(function ($default) use ($byId) {
                if ($byId->has($default['id'])) {
                    $saved = $byId[$default['id']];

                    return array_merge($default, $saved, [
                        'settings' => array_merge($default['settings'], $saved['settings'] ?? []),
                    ]);
                }

                return $default;
            })->values()->toArray();
        } else {
            $merged = self::defaultBlocks();
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
