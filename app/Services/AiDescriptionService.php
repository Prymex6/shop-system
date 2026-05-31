<?php

namespace App\Services;

use GuzzleHttp\Client;

/**
 * Generates rich product descriptions using OpenAI or Anthropic Claude.
 *
 * Configuration via Settings:
 *   ai_provider  — 'openai' | 'anthropic'  (default: openai)
 *   ai_api_key   — the API key
 *   ai_model     — model name (optional, defaults applied per provider)
 */
class AiDescriptionService
{
    private string $provider;

    private string $apiKey;

    private string $model;

    public function __construct(string $provider, string $apiKey, string $model = '')
    {
        $this->provider = $provider;
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    /**
     * Generate a full HTML product description.
     *
     * @param array $product ['title', 'price', 'specs', 'description', 'source']
     * @return array ['description' => string html, 'short_description' => string, 'meta_description' => string]
     */
    public function generate(array $product): array
    {
        $prompt = $this->buildPrompt($product);

        $raw = match ($this->provider) {
            'anthropic' => $this->callAnthropic($prompt),
            'gemini' => $this->callGemini($prompt),
            default => $this->callOpenAi($prompt),
        };

        return $this->parseResponse($raw);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PROMPT
    // ──────────────────────────────────────────────────────────────────────────

    private function buildPrompt(array $product): string
    {
        $title = $product['title'] ?? 'Produkt';
        $price = $product['price'] ? number_format((float) $product['price'], 2, ',', ' ') . ' zł' : '';
        $source = $product['source'] ?? '';

        $specsText = '';
        if (!empty($product['specs'])) {
            $specsText = "\nSpecyfikacja:\n";
            foreach ($product['specs'] as $spec) {
                $specsText .= "- {$spec['name']}: {$spec['value']}\n";
            }
        }

        $rawDesc = '';
        if (!empty($product['description'])) {
            $rawDesc = "\nOryginalny opis (przetłumacz jeśli potrzeba):\n" . mb_substr(strip_tags($product['description']), 0, 600);
        }

        return <<<PROMPT
Jesteś ekspertem od copywritingu e-commerce. Stwórz zaawansowany opis produktu w języku polskim.

Produkt: {$title}
{$price}{$specsText}{$rawDesc}

Wygeneruj TRZY rzeczy rozdzielone sekcjami:

---DESCRIPTION---
Pełny opis HTML produktu (ok. 300-400 słów). Wymagania:
- Użyj tagów <h2>, <h3>, <ul>, <li>, <strong>, <p>
- Zacznij od mocnego wprowadzenia (2-3 zdania) opisującego główną korzyść
- Sekcja "Dlaczego warto?" z listą <ul> 4-6 bulletów (konkretne korzyści, nie cechy)
- Sekcja "Specyfikacja" z tabelą <table> lub listą jeśli są dane techniczne
- Zakończ mocnym CTA: <div class="cta-block"><p class="cta-text">...</p></div>
- Styl narracyjny: przekonujący, bezpośredni, bez marketingowego bełkotu
- Nie pisz ceny ani nazwy sklepu

---SHORT---
Krótki opis (max 160 znaków) do karty produktu. Jedna zwięzła, konkretna korzyść.

---META---
Meta description SEO (max 155 znaków). Zawiera słowo kluczowe i CTA (np. "Sprawdź", "Kup teraz").

Odpowiedź TYLKO w tym formacie, bez żadnego komentarza.
PROMPT;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // API CALLS
    // ──────────────────────────────────────────────────────────────────────────

    private function callOpenAi(string $prompt): string
    {
        $model = $this->model ?: 'gpt-4o-mini';

        $client = new Client(['timeout' => 60]);
        $response = $client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $model,
                'max_tokens' => 1500,
                'temperature' => 0.7,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);

        return $data['choices'][0]['message']['content'] ?? '';
    }

    private function callAnthropic(string $prompt): string
    {
        $model = $this->model ?: 'claude-haiku-4-5-20251001';

        $client = new Client(['timeout' => 60]);
        $response = $client->post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $model,
                'max_tokens' => 1500,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);

        return $data['content'][0]['text'] ?? '';
    }

    private function callGemini(string $prompt): string
    {
        $model = $this->model ?: 'gemini-2.0-flash';

        $client = new Client(['timeout' => 60]);
        $response = $client->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}",
            [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => 1500,
                        'temperature' => 0.7,
                    ],
                ],
            ]
        );

        $data = json_decode((string) $response->getBody(), true);

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PARSE RESPONSE
    // ──────────────────────────────────────────────────────────────────────────

    private function parseResponse(string $raw): array
    {
        $description = '';
        $shortDescription = '';
        $metaDescription = '';

        if (preg_match('/---DESCRIPTION---(.+?)(?:---SHORT---|$)/s', $raw, $m)) {
            $description = trim($m[1]);
        }
        if (preg_match('/---SHORT---(.+?)(?:---META---|$)/s', $raw, $m)) {
            $shortDescription = trim(strip_tags($m[1]));
        }
        if (preg_match('/---META---(.+?)$/s', $raw, $m)) {
            $metaDescription = trim(strip_tags($m[1]));
        }

        // Fallback: if parsing failed, use the whole response as description
        if (!$description) {
            $description = $raw;
        }

        return [
            'description' => $description,
            'short_description' => mb_substr($shortDescription, 0, 160),
            'meta_description' => mb_substr($metaDescription, 0, 155),
        ];
    }
}
