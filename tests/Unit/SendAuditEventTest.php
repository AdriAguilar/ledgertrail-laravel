<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LedgerTrail\Jobs\SendAuditEvent;

it('does not throw on 422 — calls fail() instead of retrying', function () {
    Http::fake(['*' => Http::response(['message' => 'Validation error'], 422)]);

    $job = new SendAuditEvent(['event_type' => 'user.login', 'actor_id' => 'u1']);

    // 422 must NOT throw — the job marks itself as failed (no retry) and returns.
    $job->handle();

    Http::assertSentCount(1);
});

it('throws on 503 to trigger automatic retry', function () {
    Http::fake(['*' => Http::response('Service Unavailable', 503)]);

    $job = new SendAuditEvent(['event_type' => 'user.login', 'actor_id' => 'u1']);

    expect(fn () => $job->handle())->toThrow(\RuntimeException::class, 'LedgerTrail: HTTP 503');
    Http::assertSentCount(1);
});
