<?php

namespace App\Support;

class BrandingAsset
{
    private const CANDIDATE_PATHS = [
        'telematica/logos/sacat.png',
        'telematica/logos/sacat.jpg',
        'telematica/logos/sacat.jpeg',
        'telematica/logos/sacat.webp',
    ];

    public static function storageRelativePath(): ?string
    {
        foreach (self::CANDIDATE_PATHS as $path) {
            if (is_file(storage_path('app/public/' . $path))) {
                return $path;
            }
        }

        return null;
    }

    public static function publicAbsolutePath(): ?string
    {
        $relative = self::storageRelativePath();

        return $relative ? storage_path('app/public/' . $relative) : null;
    }

    public static function emailUrl(): ?string
    {
        $relative = self::storageRelativePath();

        return $relative ? asset('storage/' . $relative) : null;
    }

    public static function versionedUrl(): ?string
    {
        $relative = self::storageRelativePath();
        $absolute = self::publicAbsolutePath();

        if (! $relative || ! $absolute || ! is_file($absolute)) {
            return null;
        }

        return asset('storage/' . $relative) . '?v=' . filemtime($absolute);
    }

    public static function pdfDataUri(): ?string
    {
        $path = self::publicAbsolutePath();
        if (! $path || ! is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path);
        $content = @file_get_contents($path);

        if (! is_string($mime) || ! is_string($content) || $content === '') {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }
}
