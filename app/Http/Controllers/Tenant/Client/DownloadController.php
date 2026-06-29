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
            abort(403, 'Link do pobrania jest nieprawidłowy, wygasł lub osiągnął limit pobrań.');
        }

        $file = $link->file;

        if (!$file) {
            // Generic product download without specific file (not yet set up)
            abort(404, 'Plik nie jest jeszcze dostępny. Skontaktuj się z obsługą sklepu.');
        }

        if (!$file->exists()) {
            abort(404, 'Plik nie istnieje na serwerze.');
        }

        $this->digitalDelivery->recordDownload($link);

        return Storage::disk($file->disk)->download($file->path, $file->name);
    }
}
