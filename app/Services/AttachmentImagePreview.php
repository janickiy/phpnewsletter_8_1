<?php

namespace App\Services;

use App\Models\Attach;
use GdImage;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemException;

class AttachmentImagePreview
{
    private const MAX_BYTES = 10 * 1024 * 1024;

    private const MAX_PIXELS = 16_000_000;

    private const MAX_DIMENSION = 8192;

    public function canPreview(Attach $attachment): bool
    {
        return $this->loadImage($attachment) !== null;
    }

    public function render(Attach $attachment): ?string
    {
        $source = $this->loadImage($attachment);
        if ($source === null) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(160 / $width, 120 / $height, 1);
        $previewWidth = max(1, (int) floor($width * $scale));
        $previewHeight = max(1, (int) floor($height * $scale));
        $preview = imagecreatetruecolor($previewWidth, $previewHeight);
        if ($preview === false) {
            return null;
        }

        imagealphablending($preview, false);
        imagesavealpha($preview, true);
        imagefill($preview, 0, 0, imagecolorallocatealpha($preview, 0, 0, 0, 127));
        if (!imagecopyresampled($preview, $source, 0, 0, 0, 0, $previewWidth, $previewHeight, $width, $height)) {
            return null;
        }

        ob_start();
        try {
            return imagepng($preview) ? ob_get_contents() : null;
        } finally {
            ob_end_clean();
        }
    }

    private function loadImage(Attach $attachment): ?GdImage
    {
        $fileName = $attachment->file_name;
        if (!is_string($fileName) || $fileName === '' || str_contains($fileName, "\0") || $fileName !== basename($fileName)) {
            return null;
        }

        $disk = Storage::disk('local');
        $path = Attach::DIRECTORY.'/'.$fileName;
        $directory = realpath($disk->path(Attach::DIRECTORY));
        $absolutePath = realpath($disk->path($path));
        if ($directory === false || $absolutePath === false
            || !str_starts_with($absolutePath, $directory.DIRECTORY_SEPARATOR)
            || !is_file($absolutePath)) {
            return null;
        }

        try {
            $size = $disk->size($path);
            if ($size <= 0 || $size > self::MAX_BYTES) {
                return null;
            }

            $stream = $disk->readStream($path);
            if (!is_resource($stream)) {
                return null;
            }

            try {
                // Bound the read even if the file grows after the size check.
                $contents = stream_get_contents($stream, self::MAX_BYTES + 1);
            } finally {
                fclose($stream);
            }
        } catch (FilesystemException) {
            return null;
        }

        if ($contents === false || $contents === '' || strlen($contents) > self::MAX_BYTES) {
            return null;
        }

        // Inspect content, not filenames, and limit allocations before GD decodes.
        $info = @getimagesizefromstring($contents);
        if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            return null;
        }

        [$width, $height] = $info;
        if ($width <= 0 || $height <= 0 || $width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION
            || $width * $height > self::MAX_PIXELS) {
            return null;
        }

        $image = @imagecreatefromstring($contents);
        if ($image === false || imagesx($image) !== $width || imagesy($image) !== $height) {
            return null;
        }

        return $image;
    }
}
