<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;
use ReflectionClass;

trait GeneratesCode
{
    public static function bootGeneratesCode(): void
    {
        static::creating(function ($model) {
            if (filled($model->code)) {
                return;
            }

            $model->code = static::generateUniqueCode();
        });

        // Codes are system-owned — block mass-assignment overwrites after create.
        static::updating(function ($model) {
            if ($model->isDirty('code')) {
                $model->code = $model->getOriginal('code');
            }
        });
    }

    public static function codePrefix(): string
    {
        $ref = new ReflectionClass(static::class);

        return $ref->hasConstant('CODE_PREFIX')
            ? (string) $ref->getConstant('CODE_PREFIX')
            : 'ENT';
    }

    public static function generateUniqueCode(): string
    {
        $prefix = static::codePrefix();

        do {
            $code = $prefix.'-'.strtoupper(Str::random(6));
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }
}
