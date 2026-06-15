<?php

namespace App\Http\Controllers\Tenant\Staff;

use App\Http\Controllers\Controller;
use App\Models\Tenant\KbArticle;
use Inertia\Inertia;

class KbArticleController extends Controller
{
    public function index()
    {
        $articles = KbArticle::published()
            ->orderBy('sort_order')
            ->get(['id', 'title', 'slug', 'category', 'sort_order']);

        $categories = $articles->pluck('category')->filter()->unique()->values();

        return Inertia::render('Tenant/Staff/KnowledgeBase/Index', [
            'articles' => $articles,
            'categories' => $categories,
        ]);
    }

    public function show(string $slug)
    {
        $article = KbArticle::where('slug', $slug)->published()->firstOrFail();
        $article->increment('view_count');

        return Inertia::render('Tenant/Staff/KnowledgeBase/Show', [
            'article' => $article,
        ]);
    }
}
