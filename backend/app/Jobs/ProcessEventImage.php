<?php

namespace App\Jobs;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessEventImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        private readonly int $eventId,
        private readonly string $imagePath,
    ) {}

    public function handle(): void
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($this->imagePath)) {
            Log::warning('ProcessEventImage: file not found', ['path' => $this->imagePath]);
            return;
        }

        $absolutePath = $disk->path($this->imagePath);
        $imageInfo = @getimagesize($absolutePath);

        if (! $imageInfo) {
            Log::warning('ProcessEventImage: not a valid image', ['path' => $this->imagePath]);
            return;
        }

        [$width, $height, $type] = $imageInfo;
        $maxWidth = 1920;
        $quality = 80;

        $source = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($absolutePath),
            IMAGETYPE_PNG  => imagecreatefrompng($absolutePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($absolutePath),
            IMAGETYPE_GIF  => imagecreatefromgif($absolutePath),
            default        => null,
        };

        if (! $source) {
            Log::warning('ProcessEventImage: unsupported image type', ['type' => $type]);
            return;
        }

        if ($width > $maxWidth) {
            $newHeight = (int) round($height * ($maxWidth / $width));
            $resized = imagecreatetruecolor($maxWidth, $newHeight);

            if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }

            imagecopyresampled($resized, $source, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $resized;
        }

        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($source, $absolutePath, $quality),
            IMAGETYPE_PNG  => imagepng($source, $absolutePath, 6),
            IMAGETYPE_WEBP => imagewebp($source, $absolutePath, $quality),
            IMAGETYPE_GIF  => imagegif($source, $absolutePath),
            default        => null,
        };

        imagedestroy($source);

        Log::info('ProcessEventImage: optimized', [
            'event_id' => $this->eventId,
            'path' => $this->imagePath,
        ]);
    }
}
