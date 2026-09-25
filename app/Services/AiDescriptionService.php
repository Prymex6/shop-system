<?php

namespace App\Services;

use App\Models\Tenant\Setting;
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
        $title = $product['title'] ?? 'Product';
        $price = $product['price']
            ? app(CurrencyService::class)->formatAmount((float) $product['price'], Setting::get('currency', 'PLN'))
            : '';
        $source = $product['source'] ?? '';

        $specsText = '';
        if (!empty($product['specs'])) {
            $specsText = "\nSpecification:\n";
            foreach ($product['specs'] as $spec) {
                $specsText .= "- {$spec['name']}: {$spec['value']}\n";
            }
        }

        $rawDesc = '';
        if (!empty($product['description'])) {
            $rawDesc = "\nExisting description, translate it if that helps:\n" . mb_substr(strip_tags($product['description']), 0, 600);
        }

        // The model is told which language to answer in rather than being
        // asked in that language: a shop running in English wants English
        // copy out of it, and the prompt itself is code.
        $language = $this->outputLanguage();

        return <<<PROMPT
You are an e-commerce copywriter. Write a product description in {$language}.

Product: {$title}
{$price}{$specsText}{$rawDesc}

Produce THREE things, separated by the section markers below:

---DESCRIPTION---
The full product description as HTML, around 300-400 words:
- use <h2>, <h3>, <ul>, <li>, <strong> and <p>
- open with two or three sentences on the main benefit
- a "why buy this" section as a <ul> of four to six bullets, benefits rather than features
- a specification section as a <table> or a list, if there is technical data
- close with a call to action: <div class="cta-block"><p class="cta-text">...</p></div>
- persuasive and direct, without marketing filler
- do not write the price or the shop name

---SHORT---
A short description for the product card, 160 characters at most. One concrete benefit.

---META---
An SEO meta description, 155 characters at most, carrying the keyword and a call to action.

Answer in that format only, with no commentary.
PROMPT;
    }

    /**
     * The language the generated copy should be written in, which is the
     * language the shop itself is running in.
     */
    private function outputLanguage(): string
    {
        return match (app()->getLocale()) {
            'en' => 'English',
            default => 'Polish',
        };
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
