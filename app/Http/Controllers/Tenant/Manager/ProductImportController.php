<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Jobs\ImportProductsCsv;
use App\Models\Tenant\Setting;
use App\Services\AiDescriptionService;
use App\Services\ProductImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductImportController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // CSV IMPORT
    // ──────────────────────────────────────────────────────────────────────────

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $tenantId = tenancy()->tenant->id;
        $userId = auth('tenant')->id();

        $fileSize = $request->file('file')->getSize();

        if ($fileSize <= 100 * 1024) {
            $importService = app(ProductImportService::class);
            $result = $importService->import($request->file('file'));

            return back()->with('success',
                "Import zakończony: {$result['imported']} produktów zaimportowanych, {$result['skipped']} pominiętych."
                . (count($result['errors']) > 0 ? ' Błędy: ' . implode('; ', array_slice($result['errors'], 0, 5)) : '')
            );
        }

        // Explicit 'local' (private) disk — see CustomerImportController for why.
        $path = $request->file('file')->store("imports/{$tenantId}", 'local');
        ImportProductsCsv::dispatch($path, $tenantId, $userId);

        return back()->with('success', __('messages.csv_uploaded'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // AI DESCRIPTION GENERATOR
    // ──────────────────────────────────────────────────────────────────────────

    public function generateDescription(Request $request)
    {
        if (!$this->hasAiConfigured()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.ai_key_missing'),
            ], 422);
        }

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'numeric'],
            'specs' => ['nullable', 'array'],
            'description' => ['nullable', 'string'],
            'source' => ['nullable', 'string'],
        ]);

        try {
            $service = $this->makeAiService();
            $result = $service->generate($request->only(['title', 'price', 'specs', 'description', 'source']));

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            Log::error('AI description error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => __('messages.ai_failed', ['reason' => $e->getMessage()]),
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private function hasAiConfigured(): bool
    {
        return !empty(Setting::get('ai_api_key'));
    }

    private function makeAiService(): AiDescriptionService
    {
        return new AiDescriptionService(
            provider: Setting::get('ai_provider', 'openai'),
            apiKey: Setting::get('ai_api_key', ''),
            model: Setting::get('ai_model', ''),
        );
    }
}
