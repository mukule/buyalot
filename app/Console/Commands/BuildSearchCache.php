<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SearchCacheService;

class BuildSearchCache extends Command
{
    protected $signature = 'search:cache:build';
    protected $description = 'Builds the search cache for products, variants, brands, and categories.';

    public function handle()
    {
        $this->info('Rebuilding search cache...');
        SearchCacheService::refresh();
        $this->info('Search cache rebuilt successfully!');
    }
}
