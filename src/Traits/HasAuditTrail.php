<?php

declare(strict_types=1);

namespace LedgerTrail\Traits;

use Illuminate\Support\Str;
use LedgerTrail\Facades\AuditTrail;

trait HasAuditTrail
{
    protected static function bootHasAuditTrail(): void
    {
        static::created(function ($model): void {
            AuditTrail::log(
                Str::snake(class_basename($model)) . '.created',
                [],
                ['target_id' => (string) $model->getKey(), 'target_type' => class_basename($model)],
            );
        });

        static::updated(function ($model): void {
            AuditTrail::log(
                Str::snake(class_basename($model)) . '.updated',
                ['changes' => $model->getDirty()],
                ['target_id' => (string) $model->getKey(), 'target_type' => class_basename($model)],
            );
        });

        static::deleted(function ($model): void {
            AuditTrail::log(
                Str::snake(class_basename($model)) . '.deleted',
                [],
                ['target_id' => (string) $model->getKey(), 'target_type' => class_basename($model)],
            );
        });
    }
}
