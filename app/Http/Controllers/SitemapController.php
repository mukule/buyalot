<?php

namespace App\Http\Controllers;

use App\Services\Seo\SitemapService;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    private const TTL_MINUTES = 60;

    public function index(SitemapService $sitemap)
    {
        $xml = Cache::remember('sitemap:index', now()->addMinutes(self::TTL_MINUTES), fn () => $sitemap->index());

        return $this->xml($xml);
    }

    public function section(SitemapService $sitemap, string $section)
    {
        $xml = Cache::remember(
            "sitemap:section:{$section}",
            now()->addMinutes(self::TTL_MINUTES),
            fn () => $sitemap->section($section) ?? ''
        );

        abort_if($xml === '', 404);

        return $this->xml($xml);
    }

    public function robots()
    {
        $base = rtrim(config('app.url'), '/');

        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            '# Private / transactional areas',
            'Disallow: /admin',
            'Disallow: /distributor',
            'Disallow: /seller',
            'Disallow: /warehouse',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /customer',
            'Disallow: /customers',
            'Disallow: /notifications',
            'Disallow: /payments',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /password',
            'Disallow: /refresh/cache',
            'Disallow: /forget/cache',
            'Disallow: /switch-role',
            '',
            "Sitemap: {$base}/sitemap.xml",
            '',
        ];

        return response(implode(PHP_EOL, $lines), 200, [
            'Content-Type'  => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function xml(string $xml)
    {
        return response($xml, 200, [
            'Content-Type'  => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
