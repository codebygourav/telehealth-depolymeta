<?php

use Illuminate\Support\Facades\Storage;

if (!function_exists('storage_url')) {

    function storage_url(mixed $filePath, string $disk = 'public'): string
    {
        $filePath = normalize_storage_file_path($filePath);

        // Return default image if file path is empty
        if ($filePath === null || $filePath === '') {
            return asset('images/user-avatar.png');
        }

        // If already a full URL, return as is
        if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
            return $filePath;
        }

        // Remove leading slash if present
        $filePath = ltrim($filePath, '/');

        // Generate full URL using Storage facade
        try {
            if ($disk === 'public') {
                if (config('app.debug', false)) {
                    $storage = Storage::disk($disk);
                    if (!$storage->exists($filePath)) {
                        return asset('images/user-avatar.png');
                    }
                }
                return asset('storage/' . $filePath);
            }

            // For other disks, check existence only in debug mode
            $storage = Storage::disk($disk);
            if (config('app.debug', false) && !$storage->exists($filePath)) {
                return asset('images/user-avatar.png');
            }

            // For other disks, construct URL manually
            $baseUrl = config('app.url');
            return rtrim($baseUrl, '/') . '/storage/' . $filePath;
        } catch (\Exception $e) {
            // Fallback to default image if Storage fails
            return asset('images/user-avatar.png');
        }
    }
}

if (!function_exists('normalize_storage_file_path')) {
    function normalize_storage_file_path(mixed $filePath): ?string
    {
        if ($filePath instanceof \Stringable) {
            $filePath = (string) $filePath;
        }

        if (is_array($filePath)) {
            foreach (['path', 'url', 'file_path', 'value', 'avatar', 'image', 'src'] as $key) {
                if (array_key_exists($key, $filePath)) {
                    $normalized = normalize_storage_file_path($filePath[$key]);

                    if ($normalized !== null && $normalized !== '') {
                        return $normalized;
                    }
                }
            }

            foreach ($filePath as $item) {
                $normalized = normalize_storage_file_path($item);

                if ($normalized !== null && $normalized !== '') {
                    return $normalized;
                }
            }

            return null;
        }

        if (! is_string($filePath)) {
            return null;
        }

        $filePath = trim($filePath);

        return $filePath !== '' ? $filePath : null;
    }
}
