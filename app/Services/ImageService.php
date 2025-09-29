<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ImageService
{
    public function optimizeAndStoreImage($file, string $folder, ?string $basename = null): string
    {
        try {
            // Generate unique filename with original extension
            $basename  = $basename ?? Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $extension = $file->getClientOriginalExtension();
            $filename  = $folder . '/' . $basename . '-' . uniqid() . '.' . $extension;

            // ✅ simplest working form: use putFileAs directly
            Storage::disk('s3')->putFileAs(
                $folder,
                $file,
                basename($filename),
                ['visibility' => 'public']
            );

            Log::info('Raw image uploaded to S3 (no processing)', [
                'bucket' => config('filesystems.disks.s3.bucket'),
                'key'    => $filename,
                'url'    => Storage::disk('s3')->url($filename),
            ]);

            return $filename;

        } catch (\Exception $e) {
            Log::error('Raw image upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception("Failed to upload raw image: " . $e->getMessage());
        }
    }
}
