<?php

namespace App\Http\Requests\Concerns;

trait DetectsAutomotiveListing
{
    /**
     * True when the product is posted to an automotive marketplace (Cars & Motors).
     * Vehicles are described by make/model attributes, so brand is not required.
     */
    protected function isAutomotiveListing(): bool
    {
        $requested = $this->input('marketplaces', []);
        if (! is_array($requested)) {
            $requested = array_filter(array_map('trim', explode(',', (string) $requested)));
        }

        $automotive = collect(config('marketplace.verticals', []))
            ->filter(fn ($v) => ($v['type'] ?? 'ecommerce') === 'automotive')
            ->keys()
            ->all();

        return (bool) array_intersect(array_map('strval', $requested), $automotive);
    }
}
