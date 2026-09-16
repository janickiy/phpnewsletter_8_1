<?php

namespace App\Http\Traits;


use Illuminate\Support\Facades\Storage;

trait File
{
    /**
     * Delete a stored file from the supplied directory when it exists.
     *
     * @param string $file
     * @param string $path
     * @return bool
     */
    public static function deleteFile(string $file, string $path): bool
    {
        if (Storage::exists(sprintf('%s/%s', $path, $file))) {
            return Storage::delete(sprintf('%s/%s', $path, $file));
        }

        return false;
    }

    /**
     * Determine whether a path exists on the public storage disk.
     *
     * @param string $path
     * @return bool
     */
    public static function isExist(string $path): bool
    {
        return Storage::disk('public')->exists($path);
    }

    /**
     * Resolve a public storage path to its absolute filesystem location.
     *
     * @param string $path
     * @return string
     */
    public static function get(string $path): string
    {
        return Storage::disk('public')->path($path);
    }

    /**
     * Store the supplied file contents at a path on the public disk.
     *
     * @param string $path
     * @param string|null $file
     * @return bool
     */
    public static function download(string $path, ?string $file): bool
    {
        return $file && Storage::disk('public')->put($path, $file);
    }
}
