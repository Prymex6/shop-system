<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::with('author')
            ->when($request->search, fn ($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Tenant/Manager/Articles/Index', [
            'articles' => $query,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Manager/Articles/Form', [
            'article' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:articles,slug'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:draft,published,archived'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        // Rendered via v-html on the public blog page — purify before saving.
        $data['content'] = clean($data['content'], 'default');

        $data['author_id'] = auth('tenant')->id();

        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $article = Article::create($data);

        return redirect()->route('tenant.manager.articles.edit', $article)->with('success', __('messages.article_created'));
    }

    public function edit(Article $article)
    {
        $article->load('author');

        return Inertia::render('Tenant/Manager/Articles/Form', [
            'article' => $article,
        ]);
    }

    public function update(Request $request, Article $article)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:articles,slug,' . $article->id],
            'excerpt' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:draft,published,archived'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        // Rendered via v-html on the public blog page — purify before saving.
        $data['content'] = clean($data['content'], 'default');

        if ($data['status'] === 'published' && empty($data['published_at']) && $article->status !== 'published') {
            $data['published_at'] = now();
        }

        $article->update($data);

        return back()->with('success', __('messages.article_updated'));
    }

    public function destroy(Article $article)
    {
        $article->delete();

        return redirect()->route('tenant.manager.articles.index')->with('success', __('messages.article_deleted'));
    }

    public function toggleStatus(Request $request, Article $article)
    {
        // The status dropdown offers draft/published/archived and sends the
        // chosen value — this used to ignore the request body entirely and
        // just flip published<->draft, so picking "Zarchiwizowany" silently
        // did the wrong thing (or picking the already-active status toggled
        // it away instead of leaving it alone).
        $newStatus = $request->validate([
            'status' => 'sometimes|in:draft,published,archived',
        ])['status'] ?? ($article->status === 'published' ? 'draft' : 'published');

        $updateData = ['status' => $newStatus];

        if ($newStatus === 'published' && !$article->published_at) {
            $updateData['published_at'] = now();
        }

        $article->update($updateData);
        $label = $newStatus === 'published' ? 'opublikowany' : 'cofnięty do szkiców';

        return back()->with('success', "Artykuł {$label}.");
    }
}
