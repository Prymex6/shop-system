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
            $suggestions[] = 'Meta title jest zbyt krótki (min. 50 znaków). Aktualnie: ' . strlen($product->meta_title) . ' znaków.';
            $score -= 10;
        } elseif (strlen($product->meta_title) > 60) {
            $suggestions[] = 'Meta title jest zbyt długi (max. 60 znaków). Aktualnie: ' . strlen($product->meta_title) . ' znaków.';
            $score -= 5;
        }

        // Meta description check
        if (empty($product->meta_description)) {
            $issues[] = 'Brak meta description.';
            $score -= 20;
        } elseif (strlen($product->meta_description) < 120) {
            $suggestions[] = 'Meta description jest zbyt krótki (min. 120 znaków). Aktualnie: ' . strlen($product->meta_description) . ' znaków.';
            $score -= 10;
        } elseif (strlen($product->meta_description) > 160) {
            $suggestions[] = 'Meta description jest zbyt długi (max. 160 znaków). Aktualnie: ' . strlen($product->meta_description) . ' znaków.';
            $score -= 5;
        }

        // Slug check
        if (empty($product->slug)) {
            $issues[] = 'Brak slug.';
            $score -= 15;
        } elseif (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $product->slug)) {
            $issues[] = 'Slug nie jest poprawnym URL (tylko małe litery, cyfry i myślniki).';
            $score -= 10;
        }

        // Description word count
        $wordCount = str_word_count(strip_tags($product->description ?? ''));
        if ($wordCount < 100) {
            $suggestions[] = "Opis produktu ma za mało słów ({$wordCount}). Zalecane min. 100 słów dla lepszego SEO.";
            $score -= 15;
        }

        // Images check
        $hasImages = !empty($product->image) ||
                     (!empty($product->gallery) && count($product->gallery) > 0) ||
                     $product->images()->exists();

        if (!$hasImages) {
            $issues[] = 'Produkt nie ma żadnych zdjęć.';
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
