<?php

namespace App\Jobs;

use App\Models\Brand;
use App\Services\SearchCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshBrandCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Brand $brand;

    public function __construct(Brand $brand)
    {
        $this->brand = $brand;
    }

    public function handle(): void
    {
        info('Rebuilding brand cache triggered on class RefreshBrandCache job...');
        SearchCacheService::refreshBrand($this->brand);
    }
}
