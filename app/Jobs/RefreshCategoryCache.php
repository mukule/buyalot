<?php

namespace App\Jobs;

use App\Models\Category;
use App\Services\SearchCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshCategoryCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Category $category;

    public function __construct(Category $category)
    {
        $this->category = $category;
    }

    public function handle(): void
    {
        SearchCacheService::refreshCategory($this->category);
    }
}
