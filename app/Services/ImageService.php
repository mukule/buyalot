<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ImageService
{
    /**
     * Optimize and store an uploaded image file.
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $folder Folder inside the public disk (e.g. 'products')
     * @param string|null $basename Base name to use for the file (slugified product name, etc)
     * @return string Path relative to storage/app/public (e.g. products/my-product-5f2e3d9c8a.webp)
     * 
     * @throws \Exception on failure
     */
    public function optimizeAndStoreImage($file, string $folder, ?string $basename = null): string
    {
        try {
            // Instantiate ImageManager with GD driver explicitly
            $manager = new ImageManager(new Driver());

            // Ensure directory exists
            Storage::disk('public')->makeDirectory($folder);

            // Use fluent API to resize and encode the image
            $image = $manager->read($file)
                ->scaleDown(600)       // scales down image if bigger than 600px width
                ->toWebp(75);          // encode to webp with 75% quality

            // Generate a unique filename to avoid overwriting files
            $basename = $basename ?? Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $filename = $folder . '/' . $basename . '-' . uniqid() . '.webp';

            // Save the processed image to storage/app/public
            $image->save(storage_path('app/public/' . $filename));

            return $filename;

        } catch (\Exception $e) {
            Log::error('Image processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Failed to process image: " . $e->getMessage());
        }
    }
}
