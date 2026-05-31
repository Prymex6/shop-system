<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Str;

/**
 * Generates product advertisement banners using Pollinations.ai
 * — completely free, no API key, no registration required.
 *
 * Pollinations.ai endpoint:
 *   GET https://image.pollinations.ai/prompt/{encoded_prompt}
 *   Returns the image binary directly (JPEG).
 */
class BannerGeneratorService
{
    private const BASE_URL = 'https://image.pollinations.ai/prompt/';

    private const SIZES = [
        'facebook' => ['width' => 1200, 'height' => 628,  'label' => 'Facebook / OG (1200×628)'],
        'instagram' => ['width' => 1080, 'height' => 1080, 'label' => 'Instagram kwadrat (1080×1080)'],
        'story' => ['width' => 1080, 'height' => 1920, 'label' => 'Instagram/TikTok Story (1080×1920)'],
        'wide' => ['width' => 1920, 'height' => 600,  'label' => 'Baner strony (1920×600)'],
    ];

    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'timeout' => 60,
            'connect_timeout' => 15,
            'allow_redirects' => ['max' => 5],
        ]);
    }

    /**
     * Generate a product banner and save it to public/uploads/banners/.
     * Returns the public URL of the saved image.
     *
     * @param string $productName Product name
     * @param string $description Short description / key feature
     * @param float|null $price Price in PLN
     * @param string $size One of: facebook, instagram, story, wide
     * @param string $style Visual style: 'modern' | 'minimal' | 'bold' | 'elegant'
     * @return array ['url' => '/uploads/banners/xxx.jpg', 'size' => [...], 'prompt' => '...']
     */
    public function generate(
        string $productName,
        string $description = '',
        ?float $price = null,
        string $size = 'facebook',
        string $style = 'modern'
    ): array {
        $sizeConfig = self::SIZES[$size] ?? self::SIZES['facebook'];
        $prompt = $this->buildPrompt($productName, $description, $price, $style);
        $seed = rand(1, 99999);

        $imageUrl = self::BASE_URL . rawurlencode($prompt) . '?' . http_build_query([
            'width' => $sizeConfig['width'],
            'height' => $sizeConfig['height'],
            'model' => 'flux',
            'nologo' => 'true',
            'enhance' => 'true',
            'seed' => $seed,
        ]);

        $response = $this->http->get($imageUrl, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; ShopSystem/1.0)',
                'Accept' => 'image/*',
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Pollinations.ai zwrócił błąd: ' . $response->getStatusCode());
        }

        $body = (string) $response->getBody();
        if (strlen($body) < 5000) {
            throw new \RuntimeException('Serwis zwrócił nieprawidłowy obraz. Spróbuj ponownie.');
        }

        $filename = 'banner_' . Str::slug($productName, '_') . '_' . $size . '_' . time() . '_' . Str::random(8) . '.jpg';

        // Save under the tenant's own scoped media directory — banners embed
        // this tenant's product names/prices, so they must not be readable
        // or listable by other tenants sharing this app's shared public/ webroot.
        $tenantId = tenancy()->tenant?->id;
        if ($tenantId) {
            $storage = app(TenantStorageService::class);
            $destPath = $storage->uploadDestination($tenantId, 'banners', $filename);
            $url = $storage->publicUrl($tenantId, 'banners', $filename);
        } else {
            $destDir = public_path('uploads/banners');
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            $destPath = $destDir . '/' . $filename;
            $url = '/uploads/banners/' . $filename;
        }

        file_put_contents($destPath, $body);

        return [
            'url' => $url,
            'size' => $sizeConfig,
            'prompt' => $prompt,
            'seed' => $seed,
        ];
    }

    /**
     * Generate a direct Pollinations.ai URL (no download — for preview only).
     * Useful when you want instant preview without saving to disk.
     */
    public function previewUrl(
        string $productName,
        string $description = '',
        ?float $price = null,
        string $size = 'facebook',
        string $style = 'modern',
        int $seed = 0
    ): string {
        $sizeConfig = self::SIZES[$size] ?? self::SIZES['facebook'];
        $prompt = $this->buildPrompt($productName, $description, $price, $style);
        if (!$seed) {
            $seed = rand(1, 99999);
        }

        return self::BASE_URL . rawurlencode($prompt) . '?' . http_build_query([
            'width' => $sizeConfig['width'],
            'height' => $sizeConfig['height'],
            'model' => 'flux',
            'nologo' => 'true',
            'enhance' => 'true',
            'seed' => $seed,
        ]);
    }

    /**
     * Return all available sizes.
     */
    public static function getSizes(): array
    {
        return self::SIZES;
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function buildPrompt(
        string $productName,
        string $description,
        ?float $price,
        string $style
    ): string {
        $styleGuides = [
            'modern' => 'modern clean design, bright white background, blue and white color palette, minimalist typography',
            'minimal' => 'ultra minimalist, white space, subtle shadows, premium product photography, luxury feel',
            'bold' => 'bold vibrant colors, dynamic composition, strong contrast, energetic marketing style',
            'elegant' => 'elegant sophisticated design, gold accents, dark background, premium luxury product presentation',
        ];

        $styleText = $styleGuides[$style] ?? $styleGuides['modern'];
        $priceText = $price ? ', price tag showing ' . number_format($price, 2) . ' PLN' : '';
        $descText = $description ? ', ' . mb_substr($description, 0, 80) : '';

        return "Professional e-commerce advertisement banner for product: {$productName}{$descText}{$priceText}. {$styleText}. Commercial product photography, studio lighting, high resolution, photorealistic, 4K quality, no text overlays, suitable for online store.";
    }
}
