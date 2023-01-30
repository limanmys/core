<?php

namespace App\Models;

use Illuminate\Support\Str;

/**
 * Uses UUID Trait
 *
 * This trait makes id fields generated as UUIDs instead of incremental keys
 */
trait UsesUuid
{
    /**
     * @return void
     */
    protected static function bootUsesUuid()
    {
        static::creating(function ($model) {
            if (isset($model->id)) {
                return;
            }
            if (! $model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}
