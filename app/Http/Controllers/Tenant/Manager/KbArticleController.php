<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\KbArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class KbArticleController extends Controller
{
    public function index(Request $request)
    {
        $articles = KbArticle::when($request->category, fn ($q) => $q->where('category', $request->category))
            ->orderBy('sort_order')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Tenant/Manager/KnowledgeBase/Index', [
            'articles' => $articles,
            'filters' => $request->only(['category']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:kb_articles,slug',
            'content' => 'required|string',
            'category' => 'nullable|string|max:50',
            'is_published' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        // Currently rendered with safe {{ }} interpolation, but purify anyway —
        // a future switch to v-html for richer formatting shouldn't silently
        // reopen stored XSS here.
        $data['content'] = clean($data['content'], 'default');

        KbArticle::create($data);

        return back()->with('success', __('messages.kb_article_created'));
    }

    public function update(Request $request, KbArticle $kbArticle)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:kb_articles,slug,' . $kbArticle->id,
            'content' => 'required|string',
            'category' => 'nullable|string|max:50',
            'is_published' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $data['content'] = clean($data['content'], 'default');

        $kbArticle->update($data);

        return back()->with('success', __('messages.article_updated'));
    }

    public function destroy(KbArticle $kbArticle)
    {
        $kbArticle->delete();

        return back()->with('success', __('messages.article_deleted'));
    }
}
