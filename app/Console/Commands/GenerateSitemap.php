<?php

namespace App\Console\Commands;

use App\Services\Seo\SitemapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate
        {--url= : Base URL to use instead of APP_URL}
        {--clean : Remove stale sitemap-*.xml files from public/ first}';

    protected $description = 'Write sitemap.xml and its section files to public/ and refresh the sitemap cache';

    public function handle(): int
    {
        $service = new SitemapService($this->option('url'));
        $publicPath = public_path();

        if ($this->option('clean')) {
            foreach (File::glob($publicPath . '/sitemap-*.xml') as $old) {
                File::delete($old);
            }
        }

        $sections = $service->sections();
        $totalUrls = 0;

        foreach ($sections as $name) {
            $xml = $service->section($name);
            File::put("{$publicPath}/sitemap-{$name}.xml", $xml);
            $count = substr_count($xml, '<url>');
            $totalUrls += $count;
            Cache::forget("sitemap:section:{$name}");
            $this->line(sprintf('  %-24s %6d urls', "sitemap-{$name}.xml", $count));
        }

        File::put("{$publicPath}/sitemap.xml", $service->index());
        Cache::forget('sitemap:index');

        $this->info('sitemap.xml written with ' . count($sections) . " sections, {$totalUrls} URLs total.");

        return self::SUCCESS;
    }
}
