<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use LedgerTrail\AuditTrailManager;
use LedgerTrail\Jobs\SendAuditEvent;

it('dispatches async job by default', function () {
    Queue::fake();
    config(['ledgertrail.async' => true]);
    config(['ledgertrail.tenant_resolver' => fn () => 'tenant-uuid-001']);

    app(AuditTrailManager::class)->log('user.login', [], ['actor_id' => 'u1']);

    Queue::assertPushed(SendAuditEvent::class, function (SendAuditEvent $job): bool {
        return $job->payload['event_type'] === 'user.login';
    });
});

it('calls API synchronously when async=false', function () {
    Http::fake(['*' => Http::response(['id' => 'evt-1'], 201)]);
    config(['ledgertrail.async' => false]);
    config(['ledgertrail.tenant_resolver' => fn () => 'tenant-uuid-001']);

    app(AuditTrailManager::class)->log('user.login', [], ['actor_id' => 'u1']);

    Http::assertSentCount(1);
    Http::assertSent(fn ($req) => str_contains($req->url(), '/api/v1/events'));
});

it('redacts sensitive keys from metadata', function () {
    Http::fake(['*' => Http::response(['id' => 'evt-1'], 201)]);
    config(['ledgertrail.async' => false]);
    config(['ledgertrail.tenant_resolver' => fn () => 'tenant-uuid-001']);

    app(AuditTrailManager::class)->log(
        'user.login',
        ['username' => 'admin', 'password' => 'secret123'],
        ['actor_id' => 'u1'],
    );

    Http::assertSent(function ($request): bool {
        $body = $request->data();
        return ! array_key_exists('password', $body['metadata'] ?? [])
            && ($body['metadata']['username'] ?? null) === 'admin';
    });
});

it('throws if tenant cannot be resolved', function () {
    config(['ledgertrail.tenant_resolver' => null]);

    expect(fn () => app(AuditTrailManager::class)->log('user.login'))
        ->toThrow(\RuntimeException::class, 'cannot resolve tenant_id');
});

it('adds _pii_warning when PII detected in metadata', function () {
    Http::fake(['*' => Http::response(['id' => 'evt-1'], 201)]);
    config(['ledgertrail.async' => false]);
    config(['ledgertrail.tenant_resolver' => fn () => 'tenant-uuid-001']);

    app(AuditTrailManager::class)->log(
        'user.login',
        ['contact' => 'pii@example.com'],
        ['actor_id' => 'u1'],
    );

    Http::assertSent(fn ($req) => ($req->data()['metadata']['_pii_warning'] ?? false) === true);
});
