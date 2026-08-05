<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stockage local des images au format WebP.
 * Remplace Cloudinary : les fichiers sont stockés sur le disque (storage/public)
 * et la base de données ne contient qu'un chemin relatif (jamais de base64).
 */
class ImageStorage
{
    private const QUALITY = 85;

    // ── Stocker un fichier uploadé (WebP) ──────────────────────────────
    public static function store(UploadedFile $file, string $folder): string
    {
        return self::storeBinary($file->get(), $folder);
    }

    // ── Stocker des données binaires décodées (WebP) ───────────────────
    public static function storeFromBase64(string $binary, string $folder): string
    {
        return self::storeBinary($binary, $folder);
    }

    // ── Convertir en WebP temporaire puis écrire sur le disque ─────────
    private static function storeBinary(string $binary, string $folder): string
    {
        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            throw new \Exception('Image invalide ou illisible');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'webp_') . '.webp';
        imagewebp($image, $tmp, self::QUALITY);
        imagedestroy($image);

        try {
            $relative = Storage::disk('public')->putFileAs(
                $folder,
                $tmp,
                uniqid('', true) . '.webp'
            );
        } finally {
            @unlink($tmp);
        }

        if ($relative === false) {
            throw new \Exception('Échec de l’écriture du fichier image');
        }

        return $relative;
    }

    // ── URL publique servie depuis un chemin relatif ───────────────────
    public static function url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        return env('APP_URL') . '/storage/' . ltrim($path, '/');
    }

    // ── Supprimer un fichier (ignore les URL distantes) ────────────────
    public static function delete(?string $path): void
    {
        if (!empty($path) && !str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }
}