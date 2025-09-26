<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUlid
{
    protected static function bootHasUlid(): void
    {
        static::creating(function (Model $model) {
            if (! $model->ulid) {
                $model->ulid = Str::ulid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
