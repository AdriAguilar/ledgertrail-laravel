<?php

declare(strict_types=1);

namespace LedgerTrail\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class SendAuditEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 90];

    public function __construct(public readonly array $payload) {}

    public function handle(): void
    {
        $response = Http::withToken(config('ledgertrail.api_key'))
            ->post(config('ledgertrail.api_url') . '/api/v1/events', $this->payload);

        if ($response->status() === 422) {
            Log::error('LedgerTrail validation error — event rejected, will not retry', [
                'payload'  => $this->payload,
                'response' => $response->body(),
            ]);
            $this->fail(new \RuntimeException('LedgerTrail: 422 validation error — ' . $response->body()));
            return;
        }

        if ($response->failed()) {
            throw new \RuntimeException('LedgerTrail: HTTP ' . $response->status() . ' — ' . $response->body());
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('LedgerTrail job permanently failed', [
            'event_type' => $this->payload['event_type'] ?? 'unknown',
            'error'      => $e->getMessage(),
        ]);
    }
}
