<?php

namespace App\Services\Seo;

use App\Models\Category;
use App\Models\Products\Product;
use App\Models\Products\ProductReview;
use Illuminate\Support\Str;

/**
 * Builds the `seo` prop consumed by resources/js/components/SeoHead.vue:
 * title, description, canonical, image, robots and JSON-LD structured data.
 */
class SeoMeta
{
    public static function siteName(): string
    {
        return config('app.name');
    }

    public static function base(): string
    {
        return rtrim(config('app.url'), '/');
    }

    /** Shared Organization + WebSite (with sitelinks search box) JSON-LD. */
    public static function siteGraph(): array
    {
        $base = self::base();

        return [
            [
                '@context' => 'https://schema.org',
                '@type'    => 'Organization',
                '@id'      => "{$base}/#organization",
                'name'     => self::siteName(),
                'url'      => "{$base}/",
                'logo'     => "{$base}/logo.png",
                'sameAs'   => array_values(array_filter(config('seo.social', []))),
            ],
            [
                '@context'        => 'https://schema.org',
                '@type'           => 'WebSite',
                '@id'             => "{$base}/#website",
                'url'             => "{$base}/",
                'name'            => self::siteName(),
                'publisher'       => ['@id' => "{$base}/#organization"],
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => "{$base}/search?q={search_term_string}"],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];
    }

    public static function home(): array
    {
        return self::make([
            'title'       => config('seo.home_title') ?: 'Online Shopping in Kenya | Electronics, Appliances, Cars & More',
            'description' => config('seo.home_description') ?: 'Shop electronics, home appliances, cars, building materials and more at the best prices in Kenya. Fast delivery and secure M-Pesa payments.',
            'canonical'   => self::base() . '/',
        ]);
    }

    public static function product(Product $product, array $productData): array
    {
        $base   = self::base();
        $url    = "{$base}/products/{$product->slug}";
        $images = array_values(array_filter($productData['images'] ?? []));
        $image  = $productData['primary_image_url'] ?? ($images[0] ?? null);

        $plainDescription = $product->meta_description
            ?: Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($product->description ?? ''))), 155, '')
            ?: sprintf(
                'Buy %s%s online in Kenya at %s. Best price, genuine product, fast delivery and secure payment.',
                $product->name,
                $product->brand ? " by {$product->brand->name}" : '',
                self::siteName()
            );

        $variants = collect($productData['variants'] ?? []);
        $prices   = $variants->pluck('final_price')->filter(fn ($p) => $p !== null && $p > 0);
        $inStock  = $variants->sum('stock') > 0;

        $ratings = ProductReview::where('product_id', $product->id)
            ->selectRaw('COUNT(*) as c, AVG(rating) as a')
            ->first();

        $offer = $prices->isEmpty() ? null : (
            $prices->count() > 1
                ? [
                    '@type'         => 'AggregateOffer',
                    'priceCurrency' => config('payment.default_currency', 'KES'),
                    'lowPrice'      => (string) $prices->min(),
                    'highPrice'     => (string) $prices->max(),
                    'offerCount'    => $prices->count(),
                    'availability'  => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'url'           => $url,
                ]
                : [
                    '@type'         => 'Offer',
                    'priceCurrency' => config('payment.default_currency', 'KES'),
                    'price'         => (string) $prices->first(),
                    'availability'  => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'itemCondition' => 'https://schema.org/NewCondition',
                    'url'           => $url,
                    'seller'        => ['@type' => 'Organization', 'name' => self::siteName()],
                ]
        );

        $isCar = in_array('cars', $productData['marketplaces'] ?? [], true);
        $attrs = $productData['attributes'] ?? [];

        $schema = array_filter([
            '@context'    => 'https://schema.org',
            '@type'       => $isCar ? ['Product', 'Car'] : 'Product',
            '@id'         => "{$url}#product",
            'name'        => $product->name,
            'description' => $plainDescription,
            'image'       => $images ?: ($image ? [$image] : null),
            'sku'         => $variants->first()['sku'] ?? null,
            'brand'       => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
            'url'         => $url,
            'offers'      => $offer,
            'aggregateRating' => ($ratings && (int) $ratings->c > 0) ? [
                '@type'       => 'AggregateRating',
                'ratingValue' => round((float) $ratings->a, 1),
                'reviewCount' => (int) $ratings->c,
            ] : null,
            // Vehicle-specific fields when sold in the cars marketplace vertical.
            'vehicleModelDate'       => $isCar ? ($attrs['year'] ?? null) : null,
            'mileageFromOdometer'    => $isCar && isset($attrs['mileage']) ? ['@type' => 'QuantitativeValue', 'value' => $attrs['mileage'], 'unitCode' => 'KMT'] : null,
            'fuelType'               => $isCar ? ($attrs['fuel'] ?? null) : null,
            'vehicleTransmission'    => $isCar ? ($attrs['transmission'] ?? null) : null,
            'bodyType'               => $isCar ? ($attrs['body_type'] ?? null) : null,
            'color'                  => $isCar ? ($attrs['color'] ?? null) : null,
            'model'                  => $isCar ? ($attrs['model'] ?? null) : null,
            'manufacturer'           => $isCar && isset($attrs['make']) ? ['@type' => 'Organization', 'name' => $attrs['make']] : null,
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);

        $crumbs = array_merge(
            [['name' => 'Home', 'url' => "{$base}/"]],
            collect($productData['category_hierarchy'] ?? [])
                ->map(fn ($c) => ['name' => $c['name'], 'url' => "{$base}/{$c['slug']}"])->all(),
            [['name' => $product->name, 'url' => $url]]
        );

        return self::make([
            'title'       => $product->meta_title ?: self::productTitle($product),
            'description' => $plainDescription,
            'canonical'   => $url,
            'image'       => $image,
            'type'        => 'product',
            'jsonLd'      => [$schema, self::breadcrumbs($crumbs)],
        ]);
    }

    public static function category(Category $category, array $hierarchy, bool $filtered): array
    {
        $base = self::base();
        $url  = "{$base}/{$category->slug}";

        $crumbs = array_merge(
            [['name' => 'Home', 'url' => "{$base}/"]],
            collect($hierarchy)->map(fn ($c) => ['name' => $c['name'], 'url' => "{$base}/{$c['slug']}"])->all()
        );

        $description = $category->description
            ? Str::limit(trim(strip_tags($category->description)), 155, '')
            : "Shop {$category->name} online in Kenya at " . self::siteName() . '. Best prices, genuine products, fast delivery and secure payment.';

        return self::make([
            'title'       => "{$category->name} | Buy {$category->name} Online in Kenya",
            'description' => $description,
            'canonical'   => $url,          // filters/pagination collapse to the clean URL
            'robots'      => $filtered ? 'noindex, follow' : 'index, follow',
            'jsonLd'      => [
                self::breadcrumbs($crumbs),
                [
                    '@context' => 'https://schema.org',
                    '@type'    => 'CollectionPage',
                    'name'     => $category->name,
                    'url'      => $url,
                    'isPartOf' => ['@id' => "{$base}/#website"],
                ],
            ],
        ]);
    }

    public static function search(string $q): array
    {
        $base = self::base();

        return self::make([
            'title'       => $q !== '' ? "Search results for \"{$q}\"" : 'Search products',
            'description' => $q !== ''
                ? "Results for \"{$q}\" at " . self::siteName() . '.'
                : 'Search thousands of products at ' . self::siteName() . '.',
            'canonical'   => "{$base}/search",
            // Internal search result pages should never be indexed.
            'robots'      => $q !== '' ? 'noindex, follow' : 'index, follow',
        ]);
    }

    public static function policy(string $title, string $slug, ?string $content = null): array
    {
        $base = self::base();

        return self::make([
            'title'       => $title,
            'description' => $content
                ? Str::limit(trim(strip_tags($content)), 155, '')
                : "{$title} for " . self::siteName() . '.',
            'canonical'   => "{$base}/policies/{$slug}",
            'type'        => 'article',
        ]);
    }

    public static function deals(string $title, string $path, ?string $description = null, ?string $image = null): array
    {
        return self::make([
            'title'       => $title,
            'description' => $description
                ? Str::limit(trim(strip_tags($description)), 155, '')
                : "{$title} — limited-time prices at " . self::siteName() . '.',
            'canonical'   => self::base() . $path,
            'image'       => $image,
        ]);
    }

    // ------------------------------------------------------------------ helpers

    public static function breadcrumbs(array $crumbs): array
    {
        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)->values()->map(fn ($c, $i) => [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $c['name'],
                'item'     => $c['url'],
            ])->all(),
        ];
    }

    private static function productTitle(Product $product): string
    {
        $parts = [$product->name];
        if ($product->brand && ! Str::contains(Str::lower($product->name), Str::lower($product->brand->name))) {
            $parts[] = $product->brand->name;
        }
        $parts[] = 'Best Price in Kenya';

        return Str::limit(implode(' | ', $parts), 65, '');
    }

    private static function make(array $data): array
    {
        return [
            'title'       => $data['title'],
            'description' => $data['description'],
            'canonical'   => $data['canonical'],
            'image'       => $data['image'] ?? self::base() . '/preview.png',
            'type'        => $data['type'] ?? 'website',
            'robots'      => $data['robots'] ?? 'index, follow',
            'siteName'    => self::siteName(),
            'jsonLd'      => $data['jsonLd'] ?? [],
        ];
    }
}
