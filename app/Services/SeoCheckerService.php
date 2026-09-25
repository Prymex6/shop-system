<?php

namespace App\Services;

use App\Models\Tenant\Product;

class SeoCheckerService
{
    public function analyze(Product $product): array
    {
        $issues = [];
        $suggestions = [];
        $score = 100;

        // Meta title check
        if (empty($product->meta_title)) {
            $issues[] = 'Brak meta title.';
            $score -= 20;
        } elseif (strlen($product->meta_title) < 50) {
            $suggestions[] = __('messages.seo_meta_title_short', ['count' => strlen($product->meta_title)]);
            $score -= 10;
        } elseif (strlen($product->meta_title) > 60) {
            $suggestions[] = __('messages.seo_meta_title_long', ['count' => strlen($product->meta_title)]);
            $score -= 5;
        }

        // Meta description check
        if (empty($product->meta_description)) {
            $issues[] = 'Brak meta description.';
            $score -= 20;
        } elseif (strlen($product->meta_description) < 120) {
            $suggestions[] = __('messages.seo_meta_description_short', ['count' => strlen($product->meta_description)]);
            $score -= 10;
        } elseif (strlen($product->meta_description) > 160) {
            $suggestions[] = __('messages.seo_meta_description_long', ['count' => strlen($product->meta_description)]);
            $score -= 5;
        }

        // Slug check
        if (empty($product->slug)) {
            $issues[] = 'Brak slug.';
            $score -= 15;
        } elseif (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $product->slug)) {
            $issues[] = __('messages.seo_slug_invalid');
            $score -= 10;
        }

        // Description word count
        $wordCount = str_word_count(strip_tags($product->description ?? ''));
        if ($wordCount < 100) {
            $suggestions[] = __('messages.seo_description_thin', ['count' => $wordCount]);
            $score -= 15;
        }

        // Images check
        $hasImages = !empty($product->image) ||
                     (!empty($product->gallery) && count($product->gallery) > 0) ||
                     $product->images()->exists();

        if (!$hasImages) {
            $issues[] = __('messages.seo_no_images');
            $score -= 20;
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'details' => [
                'meta_title_length' => strlen($product->meta_title ?? ''),
                'meta_description_length' => strlen($product->meta_description ?? ''),
                'description_words' => $wordCount,
                'has_images' => $hasImages,
                'slug' => $product->slug,
            ],
        ];
    }
}
