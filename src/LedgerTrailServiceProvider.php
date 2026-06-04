<?php

declare(strict_types=1);

namespace LedgerTrail;

use Illuminate\Support\ServiceProvider;

final class LedgerTrailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ledgertrail.php', 'ledgertrail');

        $this->app->singleton(AuditTrailManager::class, fn ($app) => new AuditTrailManager());
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/ledgertrail.php' => config_path('ledgertrail.php'),
        ], 'ledgertrail-config');
    }
}
