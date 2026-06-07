<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Article;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $articles = Article::published()
            ->with('author')
            ->when($request->search, fn ($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Tenant/Client/Blog/Index', [
            'articles' => $articles,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(string $slug)
    {
        $article = Article::published()
            ->with('author')
            ->where('slug', $slug)
            ->firstOrFail();

        $related = Article::published()
            ->where('id', '!=', $article->id)
            ->latest('published_at')
            ->limit(3)
            ->get(['id', 'title', 'slug', 'excerpt', 'cover_image', 'published_at']);

        return Inertia::render('Tenant/Client/Blog/Show', [
            'article' => $article,
            'related' => $related,
        ]);
    }
}
