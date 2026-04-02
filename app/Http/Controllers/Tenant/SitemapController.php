<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Article;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Generate sitemap.xml for tenant
     */
    public function index(): Response
    {
        $domain = request()->getHost();
        $baseUrl = request()->getScheme() . '://' . $domain;

        // Crawlers hit this repeatedly and it can't be authenticated/throttled
        // away — for a catalog with thousands of products, regenerating from
        // three unbounded queries on every single request is real, avoidable
        // DB load. CacheTenancyBootstrapper keys this per tenant automatically.
        $sitemap = Cache::remember('sitemap.xml:' . $baseUrl, 3600, fn () => $this->build($baseUrl));

        return response($sitemap, 200)
            ->header('Content-Type', 'application/xml');
    }

    protected function build(string $baseUrl): string
    {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Homepage
        $sitemap .= $this->addUrl($baseUrl, now(), 'daily', '1.0');

        // Blog index
        $sitemap .= $this->addUrl($baseUrl . '/blog', now(), 'daily', '0.8');

        // Active categories
        try {
            $categories = Category::where('is_active', true)->get(['slug', 'updated_at']);
            foreach ($categories as $category) {
                $sitemap .= $this->addUrl(
                    $baseUrl . '/kategoria/' . $category->slug,
                    $category->updated_at ?? now(),
                    'weekly',
                    '0.7'
                );
            }
        } catch (\Throwable) {
            // Table may not exist yet
        }

        // Published products
        try {
            $products = Product::where('is_published', true)->get(['slug', 'updated_at']);
            foreach ($products as $product) {
                $sitemap .= $this->addUrl(
                    $baseUrl . '/produkt/' . $product->slug,
                    $product->updated_at ?? now(),
                    'weekly',
                    '0.6'
                );
            }
        } catch (\Throwable) {
        }

        // Published articles
        try {
            $articles = Article::published()->get(['slug', 'published_at', 'updated_at']);
            foreach ($articles as $article) {
                $sitemap .= $this->addUrl(
                    $baseUrl . '/blog/' . $article->slug,
                    $article->updated_at ?? $article->published_at ?? now(),
                    'monthly',
                    '0.5'
                );
            }
        } catch (\Throwable) {
        }

        $sitemap .= '</urlset>';

        return $sitemap;
    }

    /**
     * Generate robots.txt for tenant
     */
    public function robots(): Response
    {
        $domain = request()->getHost();
        $baseUrl = request()->getScheme() . '://' . $domain;

        // Previously disallowed English placeholder paths ("/checkout",
        // "/order/", "/payment/") that never existed in this Polish-language
        // app — Googlebot could freely crawl/index the real /kasa, /koszyk,
        // /moje-konto (order history, GDPR data, invoices) and, most
        // sensitive of all, /zamowienie/{token}/sledzenie — an order
        // tracking URL with an access token in the path.
        $robots = "User-agent: *\n";
        $robots .= "Allow: /\n";
        $robots .= "Disallow: /manager/\n";
        $robots .= "Disallow: /staff/\n";
        $robots .= "Disallow: /kasa\n";
        $robots .= "Disallow: /koszyk\n";
        $robots .= "Disallow: /moje-konto\n";
        $robots .= "Disallow: /zamowienie/\n";
        $robots .= "Disallow: /zamowienia/\n";
        $robots .= "Disallow: /platnosc/\n";
        $robots .= "\n";
        $robots .= "Sitemap: {$baseUrl}/sitemap.xml\n";

        return response($robots, 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Helper method to create URL entry
     */
    protected function addUrl(string $url, $lastmod, string $changefreq, string $priority): string
    {
        $xml = '<url>';
        $xml .= '<loc>' . htmlspecialchars($url) . '</loc>';
        $xml .= '<lastmod>' . $lastmod->format('Y-m-d') . '</lastmod>';
        $xml .= '<changefreq>' . $changefreq . '</changefreq>';
        $xml .= '<priority>' . $priority . '</priority>';
        $xml .= '</url>';

        return $xml;
    }
}
