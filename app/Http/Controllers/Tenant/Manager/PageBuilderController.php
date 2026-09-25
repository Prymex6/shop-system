<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PageBuilderController extends Controller
{
    public function index()
    {
        return Inertia::render('Tenant/Manager/PageBuilder/Index', [
            'pages' => Page::orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Manager/PageBuilder/Editor', [
            'page' => null,
        ]);
    }

    /** Block types the editor UI actually offers — anything else is rejected. */
    private const BLOCK_TYPES = ['hero', 'text', 'image', 'columns', 'button', 'divider', 'html', 'spacer'];

    /** Block-data keys that render as a clickable/loadable URL — javascript: here is a click-to-XSS vector. */
    private const URL_KEYS = ['button_url', 'url', 'link', 'image_url'];

    private function sanitizeBlocks(?array $blocks): array
    {
        return collect($blocks ?? [])
            ->filter(fn ($block) => in_array($block['type'] ?? null, self::BLOCK_TYPES, true))
            ->map(function (array $block) {
                $block['data'] = $block['data'] ?? [];

                // The "Custom HTML" block is the only one that renders via v-html on
                // the public page — purify it instead of trusting it verbatim.
                if ($block['type'] === 'html' && !empty($block['data']['code'])) {
                    $block['data']['code'] = clean($block['data']['code'], 'custom_embed');
                }

                foreach (self::URL_KEYS as $key) {
                    if (!empty($block['data'][$key]) && preg_match('/^\s*javascript:/i', (string) $block['data'][$key])) {
                        $block['data'][$key] = '#';
                    }
                }

                return $block;
            })
            ->values()
            ->toArray();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'blocks' => 'nullable|array',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $data['blocks'] = $this->sanitizeBlocks($data['blocks'] ?? []);
        $data['slug'] = !empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['title']);

        if (Page::where('slug', $data['slug'])->exists()) {
            throw ValidationException::withMessages([
                'slug' => [__('messages.page_url_taken')],
            ]);
        }

        $page = Page::create($data);

        return redirect()->route('tenant.manager.page-builder.edit', $page)
            ->with('success', __('messages.page_created'));
    }

    public function edit(Page $page)
    {
        return Inertia::render('Tenant/Manager/PageBuilder/Editor', [
            'page' => $page,
        ]);
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'blocks' => 'nullable|array',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        if (!empty($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);
        }

        $data['blocks'] = $this->sanitizeBlocks($data['blocks'] ?? []);

        $page->update($data);

        return back()->with('success', __('messages.page_saved'));
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return back()->with('success', __('messages.page_deleted'));
    }
}
