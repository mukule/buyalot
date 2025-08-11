<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use App\Services\ImageService;
use App\Services\ProductService;

class ProductService
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Creates a new product with optional images.
     */
    public function createProduct(array $data, ?User $user, ?array $images = null): Product
    {
        $this->applyMetadata($data);
        $this->setOwnership($data, $user);

        $product = Product::create(Arr::only($data, [
            'name', 'description', 'features', 'specifications', 'whats_in_the_box',
            'meta_title', 'meta_description', 'meta_keywords',
            'brand_id', 'subcategory_id', 'unit_id', 'stock',
            'owner_type', 'owner_id', 'price', 'discount'
        ]));

        if ($images) {
            $primaryIndex = (int) ($data['primary_image_index'] ?? -1);
            $this->handleImages($product, $images, $primaryIndex);
        }

        return $product;
    }

    /**
     * Attaches and stores product images.
     */
    protected function handleImages(Product $product, array $images, int $primaryIndex): void
    {
        foreach ($images as $index => $image) {
            if (!$image instanceof UploadedFile) {
                continue;
            }

            $basename = Str::slug($product->name);
            $path = $this->imageService->optimizeAndStoreImage($image, 'products', $basename);

            $product->images()->create([
                'image_path' => $path,
                'is_primary' => $primaryIndex === $index,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * Applies default meta fields if not provided.
     */
    protected function applyMetadata(array &$data): void
    {
        $data['meta_title'] = $data['meta_title'] ?? $data['name'];
        $data['meta_description'] = $data['meta_description'] ?? substr(strip_tags($data['description'] ?? ''), 0, 160);
        $data['meta_keywords'] = $data['meta_keywords'] ?? implode(', ', explode(' ', $data['name']));
    }

    /**
     * Sets product owner from current authenticated user.
     */
    protected function setOwnership(array &$data, ?User $user): void
    {
        $data['owner_type'] = $user?->getRoleNames()->first() ?? 'user';
        $data['owner_id'] = $user?->id;
    }


    public function updateProduct(Product $product, array $data, ?array $images = null): Product
{
    $this->applyMetadata($data);

    $product->update(Arr::only($data, [
        'name', 'description', 'features', 'specifications', 'whats_in_the_box',
        'meta_title', 'meta_description', 'meta_keywords',
        'brand_id', 'subcategory_id', 'unit_id', 'stock',
        'price', 'discount'
    ]));

    if ($images) {
        $primaryIndex = (int) ($data['primary_image_index'] ?? -1);

        // Optional: clear existing images if needed
        $product->images()->delete();

        $this->handleImages($product, $images, $primaryIndex);
    }

    return $product;
}

}
