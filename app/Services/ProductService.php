<?php

namespace App\Services;

use App\Models\Products\Product;
use App\Models\Products\ProductStatus;
use App\Models\User;
use App\Models\Variant;
use App\Models\Products\ProductVariant;
use App\Models\VariantCategory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;
use App\Models\Category;
use Illuminate\Validation\ValidationException;





class ProductService
{
    protected ImageService $imageService;

    protected array $stepHandlers = [
        1 => 'handleStep1',
        2 => 'handleStep2',
        3 => 'handleStep3',
        4 => 'handleStep4',
        5 => 'handleStep5',
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

        // Validate step
        if (!isset($this->stepHandlers[$step])) {
            throw new \InvalidArgumentException("Invalid step {$step}");
        }

        // Steps 2+ MUST have a persisted product
        if ($step > 1 && (!$product || !$product->exists)) {
            throw new \LogicException("Product must exist before step {$step}");
        }

        $method = $this->stepHandlers[$step];

        // Pull images from $data if present, fallback to $images argument
        $imagesData = $data['images'] ?? $images ?? [];

        // Call step handler: all handlers expect ($data, $user, $images, $product)
        $product = $this->$method($data, $user, $imagesData, $product);

        // Safety check after handler
        if (!$product || !$product->exists) {
            throw new \LogicException("Step {$step} did not return a persisted product");
        }

        // Update product workflow status
        $product->updateStatus(Product::STATUS_PENDING);

        $product->update([
            'current_step'       => $step + 1,
            'max_step_completed' => max((int) $product->max_step_completed, $step),
        ]);

        return $product->fresh();
    });
}



protected function handleStep1(array $data, ?User $user, ?array $images, ?Product $product): Product
{
    $this->validateStep1($data);
    $this->applyMetadata($data);

    // 🔹 Leaf category check
    if (!empty($data['category_id'])) {
        $category = Category::withCount('children')->find($data['category_id']);
        if (!$category || $category->children_count > 0) {
            throw ValidationException::withMessages([
                'category_id' => 'Invalid Category, Select the Deepest Sub Category.',
            ]);
        }
    }

    // FIX: Use withoutGlobalScopes() to find by ID
    if (!$product && !empty($data['product_id'])) {
        $product = Product::withoutGlobalScopes()->find($data['product_id']);

    }

    // FIX: Use withoutGlobalScopes() for the latest draft fallback
    if (!$product && $user) {
        $product = $user->products()
            ->withoutGlobalScopes()
            ->latestDraft()
            ->first();

    }

    // Update existing product
    if ($product) {
        $product->update(Arr::only($data, [
            'product_code',
            'name',
            'package_size',
            'brand_id',
            'category_id',
            'unit_id',
        ]));


        return $product;
    }

    // Create new product
    $this->setOwnership($data, $user);
    $data['product_code'] = $data['product_code'] ?? $this->generateProductCode();
    $product = $this->createBaseProduct($data, $user);

    Log::info('Base product created', ['product_id' => $product->id]);
    return $product;
}

protected function handleStep2(
    array $data,
    ?User $user,
    ?array $images,
    ?Product $product
): Product {
    // 1. Core existence check
    if (!$product || !$product->exists) {
        throw new \LogicException('Product record not found. Please ensure Step 1 was completed correctly.');
    }

    // 2. Ownership / Permission Guard
    // We check against the raw owner_id since we are bypassing Global Scopes
    if ($user && $user->user_type === 'seller') {
        if ((int) $product->owner_id !== (int) $user->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Unauthorized: You do not own this product.'
            );
        }
    }

    // 3. Metadata and Payload
    $this->applyMetadata($data);

    $payload = Arr::only($data, [
        'description',
        'features',
        'specifications',
        'whats_in_the_box',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'video_url',
    ]);

    // 4. Update
    if (!empty($payload)) {
        $product->update($payload);
    }

    return $product;
}



protected function handleStep3(array $data, ?User $user, ?array $images, ?Product $product): Product
{
    return DB::transaction(function () use ($data, $user, $product) {
        if (!$product || !$product->exists) {
            throw new \LogicException("Product must exist before step 3.");
        }

        // 1. Authorization and validation
        if ($user && $user->hasRole('seller') && $product->owner_id !== $user->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException("Unauthorized.");
        }

        $variantRows = collect($data['variant_rows'] ?? [])
            ->filter(fn($row) => !empty($row['values']) && is_array($row['values']));

        if ($variantRows->isEmpty()) return $product;

        $validCategories = VariantCategory::pluck('id')->flip();
        $existingVariants = $product->variants()->get()->keyBy('id');
        $submittedIds = [];
        $valuesToInsert = [];

        foreach ($variantRows as $index => $row) {
            $variantId = $row['id'] ?? null;
            
            // Map prices based on your UI inputs
            $sellingPrice = $row['buying_price'] ?? 0; 
            $markedPrice  = $row['marked_price'] ?? 0;

            $variantData = [
                'stock'         => $row['stock'] ?? 0,
                'buying_price'  => $markedPrice,
                'marked_price'  => $markedPrice,
                'regular_price' => $markedPrice,
                'selling_price' => $sellingPrice,
                'discount'      => max(0, $markedPrice - $sellingPrice),
                'sku'           => $row['sku'] ?? $this->generateSku($product, $index),
            ];

            if ($variantId && isset($existingVariants[$variantId])) {
                $productVariant = $existingVariants[$variantId];
                $productVariant->update($variantData);
                
                // Clear existing attribute links for this variant to prevent duplicates
                DB::table('product_variant_values')->where('product_variant_id', $productVariant->id)->delete();
            } else {
                $productVariant = $product->variants()->create($variantData);
            }

            $submittedIds[] = $productVariant->id;

            // 2. Map Attributes (Color, Size, etc.)
            foreach ($row['values'] as $categoryId => $value) {
                $value = trim((string)$value);
                if ($value === '' || !isset($validCategories[$categoryId])) continue;

                // FIX: firstOrCreate handles existing values even if the user types them manually
                $variantAttribute = Variant::firstOrCreate([
                    'variant_category_id' => (int) $categoryId,
                    'value'               => $value,
                ], ['is_active' => true]);

                $valuesToInsert[] = [
                    'product_variant_id' => $productVariant->id,
                    'variant_id'         => $variantAttribute->id,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }
        }

        // 3. Batch insert the pivot table links
        if (!empty($valuesToInsert)) {
            collect($valuesToInsert)->chunk(500)->each(fn($chunk) =>
                DB::table('product_variant_values')->insert($chunk->toArray())
            );
        }

        // 4. Cleanup: Remove or Deactivate variants that were not in the submission
        $product->variants()
            ->whereNotIn('id', array_filter($submittedIds))
            ->get()
            ->each(function ($variant) {
                // If variant has history (orders), just hide it. Otherwise, delete it.
                if ($variant->orders()->exists()) {
                    $variant->update(['is_active' => false]);
                } else {
                    DB::table('product_variant_values')->where('product_variant_id', $variant->id)->delete();
                    $variant->delete();
                }
            });

        // 5. Auto-publish if applicable
        $publishedStatus = ProductStatus::where('name', 'published')->first();
        if ($publishedStatus) {
            $product->updateStatus($publishedStatus->id);
        }

        return $product;
    });
}


protected function processProductVariantsOptimized(Product $product, array $variantRows): void
{

    $validCategories = VariantCategory::pluck('id')->flip();

    foreach ($variantRows as $index => $row) {

        // Guard against malformed rows
        if (empty($row['values']) || !is_array($row['values'])) {
            continue;
        }

        // Create the product variant
        $productVariant = $product->variants()->create([
            'stock'         => $row['stock'] ?? 0,
            'marked_price'  => $row['marked_price'] ?? 0,
            'buying_price'  => $row['buying_price'] ?? 0,
            'sku'           => $row['sku'] ?? $this->generateSku($product, $index),
        ]);

        $valuesToInsert = [];
        $usedCategories = []; // prevent duplicate category inserts per variant

        foreach ($row['values'] as $categoryId => $value) {

            $categoryId = (int) $categoryId;
            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            // Skip invalid or duplicate categories
            if (
                !isset($validCategories[$categoryId]) ||
                isset($usedCategories[$categoryId])
            ) {
                continue;
            }

            $usedCategories[$categoryId] = true;

            // Get or create the variant value
            $variant = Variant::firstOrCreate(
                [
                    'variant_category_id' => $categoryId,
                    'value'               => $value,
                ],
                ['is_active' => true]
            );

            // IMPORTANT: product_variant_id MUST be set explicitly
            $valuesToInsert[] = [
                'product_variant_id' => $productVariant->id,
                'variant_id'         => $variant->id,
                'created_at'         => now(),
                'updated_at'         => now(),
            ];
        }

        // Batch insert variant values
        if (!empty($valuesToInsert)) {
            $productVariant->values()->insert($valuesToInsert);
        }
    }
}


protected function handleStep4(
    array $data,
    ?User $user,
    ?array $images,
    ?Product $product
): Product {

    $imagesData = $images ?? [];

    // Log::info('STEP 4 START', [
    //     'product_id' => $product?->id,
    //     'user_id'    => $user?->id,
    //     'images_count' => count($imagesData),
    // ]);

    if (!$product || !$product->exists) {
       // Log::error('Step 4 failed: Product does not exist.');
        throw new \LogicException("Product must exist before step 4.");
    }

    if ($user && $user->hasRole('seller') && $product->owner_id !== $user->id) {
        // Log::warning('Unauthorized image edit attempt', [
        //     'product_id' => $product->id,
        //     'owner_id'   => $product->owner_id,
        //     'user_id'    => $user->id,
        // ]);
        throw new \Illuminate\Auth\Access\AuthorizationException(
            "You cannot edit this product."
        );
    }

    DB::transaction(function () use ($product, $imagesData) {

      

        $existingIds = [];
        $primaryFound = false;

        foreach ($imagesData as $index => $img) {

            $id = $img['id'] ?? null;
            $file = $img['file'] ?? null;
            $isPrimary = !empty($img['is_primary']);
            $sortOrder = $index;

           

            // --- EXISTING IMAGE WITHOUT NEW FILE ---
            if ($id && !$file) {
                $existingIds[] = $id;

                $product->images()->where('id', $id)->update([
                    'is_primary' => $isPrimary ? 1 : 0,
                    'sort_order' => $sortOrder,
                    'alt_text'   => substr($product->name, 0, 15),
                ]);

                if ($isPrimary) $primaryFound = true;

                
            }

            // --- NEW UPLOADED IMAGE (or existing with new file) ---
            if ($file instanceof \Illuminate\Http\UploadedFile) {

               // $path = $file->store('products', 'public');
               $path = $this->storeUploadedFile($file, 'products');

                $created = $product->images()->create([
                    'image_path' => $path,
                    'is_primary' => $isPrimary ? 1 : 0,
                    'sort_order' => $sortOrder,
                    'alt_text'   => substr($product->name, 0, 15),
                ]);

              

                $existingIds[] = $created->id; // keep it to prevent deletion
                if ($isPrimary) $primaryFound = true;
            }
        }

        // --- DELETE REMOVED IMAGES ---
       // Log::info('Deleting removed images', ['existing_ids_kept' => $existingIds]);

        $imagesToDelete = $product->images()
            ->when(!empty($existingIds), fn($q) => $q->whereNotIn('id', $existingIds))
            ->get();

        foreach ($imagesToDelete as $img) {
            Storage::disk('public')->delete($img->image_path);
            $img->delete();

            // Log::info('Deleted image', [
            //     'image_id' => $img->id,
            //     'path' => $img->image_path,
            // ]);
        }

        // --- ENSURE PRIMARY IMAGE EXISTS ---
        if (!$primaryFound) {
            $first = $product->images()->orderBy('sort_order')->first();

            if ($first) {
                $product->images()->update(['is_primary' => 0]);
                $first->update(['is_primary' => 1]);

              //  Log::info('Primary image auto-assigned', ['image_id' => $first->id]);
            } else {
               // Log::warning('No images exist to assign as primary');
            }
        }

       // Log::info('Step 4 transaction complete.');
    });

  //  Log::info('STEP 4 END');

    return $product->fresh('images');
}


protected function handleStep5(
    array $data,
    ?User $user,
    ?array $variantImages,
    ?Product $product
) {
    $variantImagesData = $variantImages ?? [];

    if (!$product || !$product->exists) {
       // \Log::error('Step 5 failed: Product does not exist.');
        throw new \LogicException("Product must exist before step 5.");
    }

    DB::transaction(function () use ($variantImagesData, $product, $user) {

        $imagesByVariant = collect($variantImagesData)
            ->filter(fn($img) => !empty($img['variant_id']))
            ->groupBy('variant_id');

       
        foreach ($imagesByVariant as $variantId => $imagesData) {

            $variant = \App\Models\Products\ProductVariant::with(['product', 'images'])
                ->where('product_id', $product->id)
                ->find($variantId);

            if (!$variant) {
                //\Log::warning('Variant not found or does not belong to product', ['variant_id' => $variantId]);
                continue;
            }

            if ($user && $user->hasRole('seller') && $variant->product->owner_id !== $user->id) {
                throw new \Illuminate\Auth\Access\AuthorizationException("You cannot edit this variant.");
            }

            $keptImageIds = [];
            $primaryFound = false;

            foreach ($imagesData as $index => $img) {
                $id        = $img['id'] ?? null;
                $file      = $img['file'] ?? null;
                $isPrimary = !empty($img['is_primary']);
                $sortOrder = $img['sort_order'] ?? $index;
                $preview   = $img['preview'] ?? null; // new: handle blob URLs

                // UPDATE EXISTING IMAGE
                if ($id) {
                    $variantImage = $variant->images->firstWhere('id', $id);
                    if ($variantImage) {
                        if ($file instanceof \Illuminate\Http\UploadedFile) {
                            \Storage::disk('public')->delete($variantImage->image_path);
                          //  $path = $file->store('product_variants', 'public');
                          $path = $this->storeUploadedFile($file, 'product_variants');
                            $variantImage->image_path = $path;
                        }
                        $variantImage->update([
                            'is_primary' => $isPrimary ? 1 : 0,
                            'sort_order' => $sortOrder,
                            'alt_text'   => 'Variant Image',
                        ]);
                        $keptImageIds[] = $variantImage->id;
                        if ($isPrimary) $primaryFound = true;
                        continue;
                    }
                }

                // CREATE NEW IMAGE
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                   // $path = $file->store('product_variants', 'public');
                   $path = $this->storeUploadedFile($file, 'product_variants');
                } elseif ($preview && preg_match('/^blob:|^http/', $preview)) {
                    // fallback: save existing image URL as path (you might copy it to storage if needed)
                    $path = $preview;
                } else {
                    continue; // skip invalid row
                }

                $created = $variant->images()->create([
                    'image_path' => $path,
                    'is_primary' => $isPrimary ? 1 : 0,
                    'sort_order' => $sortOrder,
                    'alt_text'   => 'Variant Image',
                ]);

                $keptImageIds[] = $created->id;
                if ($isPrimary) $primaryFound = true;

              
            }

            // DELETE REMOVED IMAGES
            $imagesToDelete = $variant->images()
                ->when(!empty($keptImageIds), fn($q) => $q->whereNotIn('id', $keptImageIds))
                ->get();

            foreach ($imagesToDelete as $img) {
                if (preg_match('/storage/', $img->image_path)) {
                    \Storage::disk('public')->delete($img->image_path);
                }
                $img->delete();
                // \Log::info('Deleted variant image', [
                //     'image_id' => $img->id,
                //     'path'     => $img->image_path
                // ]);
            }

            // ENSURE PRIMARY IMAGE
            if (!$primaryFound) {
                $first = $variant->images()->orderBy('sort_order')->first();
                if ($first) {
                    $variant->images()->update(['is_primary' => 0]);
                    $first->update(['is_primary' => 1]);
                    // \Log::info('Primary image auto-assigned', [
                    //     'variant_id' => $variantId,
                    //     'image_id'   => $first->id
                    // ]);
                }
            }
        }

       // \Log::info('Step 5 transaction complete.');
    });

   // \Log::info('STEP 5 END', ['product_id' => $product?->id]);

    return $product->fresh('variants.images');
}


protected function storeUploadedFile(UploadedFile $file, string $directory): string
{
    $extension = $file->getClientOriginalExtension();
    $safeName  = uniqid() . '.' . $extension; // ignore original name entirely
    
    return $file->storeAs($directory, $safeName, 'public');
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
        'marked_price'  => $variantData['marked_price'] ?? 0,
        'buying_price'  => $variantData['buying_price'] ?? 0,
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

    // protected function generateSku(Product $product, int $index): string
    // {
    //     return $product->product_code . '-V' . ($index + 1);
    // }

    protected function generateSku(Product $product, int $index): string
{
    do {
        $sku = $product->product_code . '-V' . strtoupper(Str::random(6));
    } while (ProductVariant::where('sku', $sku)->exists());

    return $sku;
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
        $roles = $user->getRoleNames();
        $data['owner_type'] = $data['owner_type'] ?? ($roles->first() ?? 'user');
        $data['owner_id']   = $data['owner_id'] ?? $user->id;
    } else {
        $data['owner_type'] = $data['owner_type'] ?? 'admin';
        $data['owner_id']   = $data['owner_id'] ?? null;
    }
}


}
