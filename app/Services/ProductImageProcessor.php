<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Re-encodes an uploaded product image into a size-capped "hero" version
 * plus a small thumbnail, instead of storing the raw upload as-is. Mirrors
 * SettingsController::optimizeAndSave()'s GD approach and its fallback
 * philosophy: the 'image' validation rule already ran a real getimagesize()
 * check before this is called, so a missing GD extension or a decode
 * failure here falls back to storing the original untouched rather than
 * blocking an otherwise-valid upload.
 */
class ProductImageProcessor
{
    private const HERO_MAX_DIM = 1600;

    private const THUMB_MAX_DIM = 320;

    private const JPEG_QUALITY_HERO = 82;

    private const JPEG_QUALITY_THUMB = 75;

    /**
     * @return array{path: string, thumbnail_path: string, width: ?int, height: ?int}
     */
    public function process(UploadedFile $file, string $disk, string $directory): array
    {
        $directory = trim($directory, '/');
        $heroName = Str::random(40) . '.jpg';
        $thumbName = Str::random(40) . '_thumb.jpg';
        $heroRelative = $directory . '/' . $heroName;
        $thumbRelative = $directory . '/' . $thumbName;
        $storageDisk = Storage::disk($disk);

        if (!extension_loaded('gd')) {
            Log::warning('ProductImageProcessor: GD unavailable, storing original without resizing');
            $stored = $file->storeAs($directory, $heroName, $disk);

            return ['path' => $stored, 'thumbnail_path' => $stored, 'width' => null, 'height' => null];
        }

        $mimeType = $file->getMimeType();
        $src = match ($mimeType) {
            'image/png' => @imagecreatefrompng($file->getRealPath()),
            'image/gif' => @imagecreatefromgif($file->getRealPath()),
            'image/webp' => @imagecreatefromwebp($file->getRealPath()),
            default => @imagecreatefromjpeg($file->getRealPath()),
        };

        if (!$src) {
            Log::warning('ProductImageProcessor: GD could not decode an upload that passed image validation, storing original', ['mime' => $mimeType]);
            $stored = $file->storeAs($directory, $heroName, $disk);

            return ['path' => $stored, 'thumbnail_path' => $stored, 'width' => null, 'height' => null];
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        $storageDisk->makeDirectory($directory);

        [$heroW, $heroH] = $this->resizeAndSave($src, $origW, $origH, self::HERO_MAX_DIM, $storageDisk->path($heroRelative), self::JPEG_QUALITY_HERO);
        $this->resizeAndSave($src, $origW, $origH, self::THUMB_MAX_DIM, $storageDisk->path($thumbRelative), self::JPEG_QUALITY_THUMB);

        imagedestroy($src);

        return [
            'path' => $heroRelative,
            'thumbnail_path' => $thumbRelative,
            'width' => $heroW,
            'height' => $heroH,
        ];
    }

    /** @return array{0: int, 1: int} [width, height] actually written */
    private function resizeAndSave($src, int $origW, int $origH, int $maxDim, string $destPath, int $quality): array
    {
        if ($origW > $maxDim || $origH > $maxDim) {
            $ratio = min($maxDim / $origW, $maxDim / $origH);
            $newW = max(1, (int) round($origW * $ratio));
            $newH = max(1, (int) round($origH * $ratio));
        } else {
            $newW = $origW;
            $newH = $origH;
        }

        $dst = imagecreatetruecolor($newW, $newH);
        // Preserve transparency for PNG/GIF sources.
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagejpeg($dst, $destPath, $quality);
        imagedestroy($dst);

        return [$newW, $newH];
    }
}
