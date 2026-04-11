<?php

namespace App\Observers;

use App\Models\Policy\Policy;
use Illuminate\Support\Facades\Cache;

class PolicyObserver
{
    public function saved(Policy $policy): void
    {
        Cache::forget('customer_policies');
    }

    public function deleted(Policy $policy): void
    {
        Cache::forget('customer_policies');
    }
}
