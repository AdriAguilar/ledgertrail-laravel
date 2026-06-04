<?php

declare(strict_types=1);

namespace LedgerTrail\Facades;

use Illuminate\Support\Facades\Facade;
use LedgerTrail\AuditTrailManager;

/**
 * @method static void log(string $eventType, array $metadata = [], array $options = [])
 *
 * @see AuditTrailManager
 */
final class AuditTrail extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AuditTrailManager::class;
    }
}
