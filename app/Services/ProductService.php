<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use App\Models\ProductVariant;
use App\Models\VariantCategory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ImageService;

class ProductService
{
    protected ImageService $imageService;

    protected array $stepHandlers = [
        1 => 'handleStep1',
        2 => 'handleStep2',
        3 => 'handleStep3',
        4 => 'handleStep4',
    ];

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function createOrUpdateProductStep(
        int $step,
        array $data,
        ?User $user = null,
        ?array $images = null,
        ?Product $product = null
    ): Product {
        return DB::transaction(function () use ($step, $data, $user, $images, $product) {
            Log::info("Processing product step {$step}", ['product_id' => $product?->id]);

            if (!isset($this->stepHandlers[$step])) {
                throw new \InvalidArgumentException("Invalid step {$step}");
            }

            $method = $this->stepHandlers[$step];
            $product = $this->$method($data, $user, $images, $product);

             if ($product) {
            $product->updateStatus(1); // <-- sets status_id = 1
        }

            $nextStep = $step + 1;
            $product->update([
                'current_step'       => $nextStep,
                'max_step_completed' => max($product->max_step_completed, $step),
            ]);

            return $product->fresh();
        });
    }

    /**
     * Step 1: Create or update base product.
     */
  
   
  protected function handleStep1(array $data, ?User $user, ?array $images, ?Product $product): Product
{
    // Log incoming data for debugging
    Log::info('handleStep1 called', [
        'data' => $data,
        'user_id' => $user?->id,
        'product_param_id' => $product?->id,
        'images_count' => $images ? count($images) : 0,
    ]);

    $this->validateStep1($data);
    $this->applyMetadata($data);

    // Use product_id from incoming data if $product is null
    if (!$product && !empty($data['product_id'])) {
        $product = Product::find($data['product_id']);
        Log::info('Fetched product from product_id', [
            'product_id' => $product?->id,
        ]);
    }

    // Fall back to latest draft for the user if still null
    if (!$product && $user) {
        $product = $user->products()->latestDraft()->first();
        Log::info('Fetched latest draft', [
            'product_id' => $product?->id,
        ]);
    }

    // Update existing product
    if ($product) {
        $product->update(Arr::only($data, [
            'product_code',
            'name',
            'brand_id',
            'category_id',
            'unit_id',
        ]));
        Log::info('Step 1 updated existing product', ['product_id' => $product->id]);
        return $product;
    }

    // Create new product
    $this->setOwnership($data, $user);
    $data['product_code'] = $data['product_code'] ?? $this->generateProductCode();
    $product = $this->createBaseProduct($data, $user);

    Log::info('Base product created', ['product_id' => $product->id]);
    return $product;
}




    protected function handleStep2(array $data, ?User $user, ?array $images, ?Product $product): Product
    {
        if (!$product) {
            throw new \InvalidArgumentException("Product must exist before step 2.");
        }

        $this->applyMetadata($data);
        $product->update(Arr::only($data, [
            'description', 'features', 'specifications', 'whats_in_the_box',
            'meta_title', 'meta_description', 'meta_keywords'
        ]));

        Log::info('Product description & SEO updated', ['product_id' => $product->id]);
        return $product;
    }

    protected function handleStep3(array $data, ?User $user, ?array $images, ?Product $product): Product
{
    if (!$product) throw new \InvalidArgumentException("Product must exist before step 3.");

    if (!empty($data['variant_rows']) && is_array($data['variant_rows'])) {
        $product->variants()->delete();  // ✅ Clears old variants
        $this->processProductVariants($product, $data['variant_rows']);
        Log::info('Product variants updated', ['product_id' => $product->id]);
    }

    return $product;
}



protected function handleStep4(array $data, ?User $user, ?array $images, ?Product $product): Product
{
    if (!$product) {
        throw new \InvalidArgumentException("Product must exist before step 4.");
    }

    // Prefer images from $data
    $images = $data['images'] ?? $images ?? [];

    \Log::info('handleStep4 called', [
        'product_id' => $product->id,
        'image_count' => count($images),
        'sample' => array_slice($images, 0, 2),
    ]);

    if (!empty($images)) {
        $primaryIndex = $data['primary_image_index'] ?? 0;

        $newImages = [];
        $submittedExistingIds = [];

        foreach ($images as $index => $img) {
            if (is_array($img) && !empty($img['id'])) {
                // Track existing IDs submitted
                $submittedExistingIds[] = $img['id'];
            } elseif ($img instanceof \Illuminate\Http\UploadedFile) {
                $newImages[] = [
                    'file' => $img,
                    'index' => $index,
                ];
            } elseif (is_array($img) && !empty($img['file'])) {
                $newImages[] = $img;
            }
        }

        // Remove images that were deleted in the frontend
        $product->images()
            ->whereNotIn('id', $submittedExistingIds)
            ->get()
            ->each(function ($img) {
                if (\Storage::exists($img->image_path)) {
                    \Storage::delete($img->image_path);
                }
                $img->delete();
            });

        // Update existing images: primary flag, sort order, alt_text
        foreach ($images as $index => $img) {
            if (is_array($img) && !empty($img['id'])) {
                $isPrimary = ($primaryIndex === $index);
                $sortOrder = $img['sort_order'] ?? $index;
                $altText = substr($product->name, 0, 15);

                $product->images()->where('id', $img['id'])->update([
                    'is_primary' => $isPrimary ? 1 : 0,
                    'sort_order' => $sortOrder,
                    'alt_text' => $altText,
                ]);
            }
        }

        // Process new uploads
        if (!empty($newImages)) {
            $this->processProductImages($product, $newImages, $primaryIndex);
        }

        // Ensure at least one primary exists
        if (!$product->images()->where('is_primary', true)->exists()) {
            $first = $product->images()->orderBy('sort_order')->first();
            if ($first) {
                $first->update(['is_primary' => true]);
            }
        }

        \Log::info('handleStep4 completed', [
            'product_id' => $product->id,
            'final_count' => $product->images()->count(),
            'has_primary' => $product->images()->where('is_primary', true)->exists(),
        ]);
    }

    return $product;
}


protected function processProductImages(Product $product, array $images, int $primaryIndex): void
{
    foreach ($images as $index => $image) {
        $file = $image instanceof \Illuminate\Http\UploadedFile
            ? $image
            : (is_array($image) ? ($image['file'] ?? null) : null);

        if ($file instanceof \Illuminate\Http\UploadedFile) {
            $basename = Str::slug($product->name) . '-' . time();
            $path = $this->imageService->optimizeAndStoreImage($file, 'products', $basename);

            $product->images()->create([
                'image_path' => $path,
                'is_primary' => $primaryIndex === $index ? 1 : 0,
                'sort_order' => $index,
                'alt_text' => substr($product->name, 0, 15),
            ]);
        }
    }

    // Ensure at least one primary exists
    if (!$product->images()->where('is_primary', 1)->exists()) {
        $firstImage = $product->images()->orderBy('sort_order')->first();
        if ($firstImage) {
            $firstImage->update(['is_primary' => 1]);
        }
    }

    \Log::info('processProductImages completed', [
        'product_id' => $product->id,
        'final_count' => $product->images()->count(),
        'primary_index' => $primaryIndex,
        'has_primary' => $product->images()->where('is_primary', 1)->exists(),
    ]);
}



    protected function createBaseProduct(array $data, ?User $user = null): Product
    {
        return Product::create([
            'product_code'       => $data['product_code'],
            'name'               => $data['name'],
            'brand_id'           => $data['brand_id'] ?? null,
            'category_id'     => $data['category_id'] ?? null,
            'unit_id'            => $data['unit_id'] ?? null,
            'owner_type'         => $data['owner_type'] ?? ($user ? 'seller' : 'admin'),
            'owner_id'           => $data['owner_id'] ?? ($user ? $user->id : null),
            'status'             => $data['status'] ?? 0,
            'current_step'       => 1,
            'max_step_completed' => 0,
        ]);
    }

    protected function processProductVariants(Product $product, array $variantRows): void
    {
        foreach ($variantRows as $index => $row) {
            if (empty($row['values']) || !is_array($row['values'])) {
                Log::warning('Skipping invalid variant row', ['index' => $index]);
                continue;
            }

            $productVariant = $this->createProductVariant($product, $row, $index);
            $this->processVariantValues($productVariant, $row['values'], $index);
        }
    }

    protected function createProductVariant(Product $product, array $variantData, int $index): ProductVariant
    {
        return $product->variants()->create([
            'stock'         => $variantData['stock'] ?? 0,
            'regular_price' => $variantData['regular_price'] ?? 0,
            'selling_price' => $variantData['selling_price'] ?? ($variantData['regular_price'] ?? 0),
            'sku'           => $variantData['sku'] ?? $this->generateSku($product, $index),
        ]);
    }

   
    protected function processVariantValues(ProductVariant $productVariant, array $values, int $rowIndex): void
{
    foreach ($values as $categoryId => $value) {
        if (empty(trim($value))) continue;

        $categoryId = (int)$categoryId;
        if (!VariantCategory::where('id', $categoryId)->exists()) {
            throw new \InvalidArgumentException("Variant category ID {$categoryId} does not exist");
        }

        $variant = Variant::firstOrCreate(
            ['variant_category_id' => $categoryId, 'value' => trim($value)],
            ['is_active' => true]
        );

        $productVariant->values()->create(['variant_id' => $variant->id]);
    }
}




    protected function validateStep1(array $data): void
    {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Product name is required');
        }
    }

    protected function generateProductCode(): string
    {
        return 'PROD-' . strtoupper(Str::random(8));
    }

    protected function generateSku(Product $product, int $index): string
    {
        return $product->product_code . '-V' . ($index + 1);
    }

    protected function applyMetadata(array &$data): void
    {
        $data['meta_title']       = $data['meta_title'] ?? $data['name'];
        $data['meta_description'] = $data['meta_description'] ?? substr(strip_tags($data['description'] ?? ''), 0, 160);
        $data['meta_keywords']    = $data['meta_keywords'] ?? implode(', ', explode(' ', $data['name']));
    }

   protected function setOwnership(array &$data, ?User $user): void
{
    if ($user) {
        $roles = $user->getRoleNames(); // returns a collection of role names
        $data['owner_type'] = $data['owner_type'] ?? ($roles->first() ?? 'user'); // fallback to 'user'
        $data['owner_id']   = $data['owner_id'] ?? $user->id;
    } else {
        $data['owner_type'] = $data['owner_type'] ?? 'admin';
        $data['owner_id']   = $data['owner_id'] ?? null;
    }
}

}
