<?php

declare(strict_types=1);

namespace LedgerTrail\Tests;

use LedgerTrail\LedgerTrailServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [LedgerTrailServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('ledgertrail.api_key', 'test-api-key');
        $app['config']->set('ledgertrail.api_url', 'https://api.ledgertrail.test');
        $app['config']->set('ledgertrail.async', false);
        $app['config']->set('ledgertrail.queue', 'ledgertrail');
        $app['config']->set('ledgertrail.tenant_resolver', null);
        $app['config']->set('ledgertrail.redact', ['password', 'token', 'secret', 'credit_card']);
    }
}
