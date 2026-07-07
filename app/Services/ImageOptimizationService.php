<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * Compresses uploaded images to WebP without visible quality loss.
 *
 * Products/cars can carry many images, so every upload is downscaled to a sane
 * max dimension and re-encoded as WebP (~25-35% smaller than JPEG at the same
 * perceived quality). Re-encoding via GD also strips EXIF/metadata. A separate
 * lightweight thumbnail is generated for grids/cards.
 *
 * Falls back to storing the original file if anything goes wrong, so uploads
 * never fail because of optimization.
 */
class ImageOptimizationService
{
    /** Max width/height for the full image. */
    public const MAX_DIMENSION = 1600;

    /** Max dimension for the generated thumbnail. */
    public const THUMB_DIMENSION = 400;

    /** WebP quality — 82 is visually lossless for photos while cutting size. */
    public const QUALITY = 82;

    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Optimize + store an uploaded image. Returns the stored (relative) path.
     *
     * @param  bool  $withThumbnail  also write a `_thumb` sibling
     */
    public function optimizeAndStore(UploadedFile $file, string $directory, bool $withThumbnail = true): string
    {
        $basename = uniqid('', true) . '-' . Str::random(6);
        $path = trim($directory, '/') . '/' . $basename . '.webp';

        try {
            $image = $this->manager->read($file->getRealPath());

            // Downscale only (never upscale) to keep quality; preserves aspect ratio.
            $image->scaleDown(width: self::MAX_DIMENSION, height: self::MAX_DIMENSION);

            Storage::disk('public')->put($path, (string) $image->toWebp(self::QUALITY));

            if ($withThumbnail) {
                $thumb = $this->manager->read($file->getRealPath())
                    ->scaleDown(width: self::THUMB_DIMENSION, height: self::THUMB_DIMENSION);
                Storage::disk('public')->put(
                    $this->thumbPath($path),
                    (string) $thumb->toWebp(self::QUALITY),
                );
            }

            return $path;
        } catch (\Throwable $e) {
            Log::warning('Image optimization failed; storing original.', ['error' => $e->getMessage()]);

            // Fallback: store the original untouched so the upload still succeeds.
            $ext = $file->getClientOriginalExtension() ?: 'jpg';
            return $file->storeAs(trim($directory, '/'), $basename . '.' . $ext, 'public');
        }
    }

    /** Convention for a path's thumbnail sibling: foo.webp -> foo_thumb.webp */
    public function thumbPath(string $path): string
    {
        $dot = strrpos($path, '.');
        if ($dot === false) {
            return $path . '_thumb';
        }
        return substr($path, 0, $dot) . '_thumb' . substr($path, $dot);
    }
}
