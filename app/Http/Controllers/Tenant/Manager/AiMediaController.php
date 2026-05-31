<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Models\Tenant\Setting;
use App\Services\BannerGeneratorService;
use App\Services\TenantStorageService;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AiMediaController extends Controller
{
    public function __construct(protected BannerGeneratorService $bannerService) {}

    // ──────────────────────────────────────────────────────────────────────────
    // BANNER GENERATOR PAGE (standalone)
    // ──────────────────────────────────────────────────────────────────────────

    public function bannerPage()
    {
        $products = Product::published()
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')->limit(1)])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'name', 'price', 'short_description']);

        return Inertia::render('Tenant/Manager/Marketing/BannerGenerator', [
            'products' => $products,
            'sizes' => BannerGeneratorService::getSizes(),
            'klingKeySet' => !empty(Setting::get('kling_api_key')),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GENERATE BANNER PREVIEW URL (instant, no download)
    // Returns a direct Pollinations.ai URL — no server-side image saving.
    // The user sees a preview and can decide to save.
    // ──────────────────────────────────────────────────────────────────────────

    public function previewBanner(Request $request)
    {
        $request->validate([
            'product_name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:300'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'size' => ['nullable', 'string', 'in:facebook,instagram,story,wide'],
            'style' => ['nullable', 'string', 'in:modern,minimal,bold,elegant'],
            'seed' => ['nullable', 'integer', 'min:1', 'max:999999'],
        ]);

        try {
            $url = $this->bannerService->previewUrl(
                productName: $request->input('product_name'),
                description: $request->input('description', ''),
                price: $request->input('price') ? (float) $request->input('price') : null,
                size: $request->input('size', 'facebook'),
                style: $request->input('style', 'modern'),
                seed: (int) $request->input('seed', rand(1, 99999)),
            );

            return response()->json(['success' => true, 'url' => $url]);
        } catch (\Throwable $e) {
            Log::warning('Banner preview error', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SAVE BANNER (download from Pollinations + store locally)
    // ──────────────────────────────────────────────────────────────────────────

    public function saveBanner(Request $request)
    {
        $request->validate([
            'product_name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:300'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'size' => ['nullable', 'string', 'in:facebook,instagram,story,wide'],
            'style' => ['nullable', 'string', 'in:modern,minimal,bold,elegant'],
        ]);

        try {
            $result = $this->bannerService->generate(
                productName: $request->input('product_name'),
                description: $request->input('description', ''),
                price: $request->input('price') ? (float) $request->input('price') : null,
                size: $request->input('size', 'facebook'),
                style: $request->input('style', 'modern'),
            );

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            Log::error('Banner save error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => __('messages.banner_generation_failed', ['reason' => $e->getMessage()]),
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LIST SAVED BANNERS
    // ──────────────────────────────────────────────────────────────────────────

    public function listBanners()
    {
        $tenantId = tenancy()->tenant?->id;
        $storage = app(TenantStorageService::class);
        $dir = $storage->mediaPath($tenantId, 'banners');
        $files = [];

        if (is_dir($dir)) {
            foreach (glob($dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) as $path) {
                $files[] = [
                    'url' => $storage->publicUrl($tenantId, 'banners', basename($path)),
                    'name' => basename($path),
                    'size' => filesize($path),
                    'modified' => filemtime($path),
                ];
            }
            usort($files, fn ($a, $b) => $b['modified'] - $a['modified']);
        }

        return response()->json(array_slice($files, 0, 50));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DELETE BANNER
    // ──────────────────────────────────────────────────────────────────────────

    public function deleteBanner(Request $request)
    {
        $request->validate(['filename' => ['required', 'string', 'regex:/^banner_[\w\-]+\.(jpg|jpeg|png|webp)$/i']]);

        $tenantId = tenancy()->tenant?->id;
        $storage = app(TenantStorageService::class);
        $path = $storage->mediaPath($tenantId, 'banners') . '/' . basename($request->input('filename'));

        if (!file_exists($path)) {
            return response()->json(['success' => false, 'message' => 'Plik nie istnieje'], 404);
        }

        unlink($path);

        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // VIDEO AD — generate via Kling AI (free tier) if key available,
    // otherwise return instructions for manual approach
    // ──────────────────────────────────────────────────────────────────────────

    public function generateVideo(Request $request)
    {
        $request->validate([
            'product_name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:300'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $klingKey = Setting::get('kling_api_key', '');

        if ($klingKey) {
            return $this->generateKlingVideo($request, $klingKey);
        }

        // No key — return a guide on how to get a free key
        return response()->json([
            'success' => false,
            'no_key' => true,
            'message' => __('messages.kling_key_required'),
            'instructions' => [
                'Wejdź na https://platform.kling.ai',
                'Załóż bezpłatne konto (email + weryfikacja)',
                'Kliknij "API Keys" → "Create API Key"',
                'Skopiuj klucz i wklej w Ustawienia → Integracje → Kling API Key',
                'Darmowy plan: 66 video/miesiąc (5 sekund każdy)',
            ],
            'settings_url' => route('tenant.manager.settings'),
        ], 422);
    }

    private function generateKlingVideo(Request $request, string $apiKey): JsonResponse
    {
        $productName = $request->input('product_name');
        $description = $request->input('description', '');
        $price = $request->input('price');
        $imageUrl = $request->input('image_url', '');

        $descText = $description ? " {$description}." : '';
        $priceText = $price ? ' Cena: ' . number_format((float) $price, 2) . ' PLN.' : '';
        $prompt = "Professional product advertisement video for: {$productName}.{$descText} {$priceText} Clean white background, smooth camera movement, product showcase, commercial quality, no text overlays.";

        $payload = [
            'model' => 'kling-v1',
            'prompt' => $prompt,
            'duration' => '5',
            'aspect_ratio' => '16:9',
            'cfg_scale' => 0.5,
        ];

        if ($imageUrl) {
            $payload['image_url'] = $imageUrl;
            unset($payload['model']);
            $endpoint = 'https://api.klingai.com/v1/images/image2video';
        } else {
            $endpoint = 'https://api.klingai.com/v1/videos/text2video';
        }

        try {
            $client = new Client(['timeout' => 30]);
            $response = $client->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $data = json_decode((string) $response->getBody(), true);

            if (isset($data['data']['task_id'])) {
                return response()->json([
                    'success' => true,
                    'task_id' => $data['data']['task_id'],
                    'message' => __('messages.video_generating'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => __('messages.kling_unexpected_response', ['body' => json_encode($data)]),
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Kling video error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => __('messages.kling_failed', ['reason' => $e->getMessage()]),
            ], 500);
        }
    }

    // Poll Kling task status
    public function videoStatus(Request $request)
    {
        $request->validate(['task_id' => ['required', 'string']]);

        $klingKey = Setting::get('kling_api_key', '');
        if (!$klingKey) {
            return response()->json(['success' => false, 'message' => 'Brak klucza API'], 422);
        }

        try {
            $client = new Client(['timeout' => 15]);
            $response = $client->get('https://api.klingai.com/v1/videos/text2video/' . $request->input('task_id'), [
                'headers' => ['Authorization' => 'Bearer ' . $klingKey],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            $status = $data['data']['task_status'] ?? 'processing';
            $videos = $data['data']['task_result']['videos'] ?? [];

            return response()->json([
                'success' => true,
                'status' => $status,
                'done' => $status === 'succeed',
                'url' => $videos[0]['url'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
