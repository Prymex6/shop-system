<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Services\DigitalDeliveryService;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function __construct(private DigitalDeliveryService $digitalDelivery) {}

    public function download(string $token)
    {
        $link = $this->digitalDelivery->resolveToken($token);

        if (!$link) {
            abort(403, __('messages.download_link_invalid'));
        }

        $file = $link->file;

        if (!$file) {
            // Generic product download without specific file (not yet set up)
            abort(404, __('messages.download_not_ready'));
        }

        if (!$file->exists()) {
            abort(404, __('messages.file_missing_on_server'));
        }

        $this->digitalDelivery->recordDownload($link);

        return Storage::disk($file->disk)->download($file->path, $file->name);
    }
}
