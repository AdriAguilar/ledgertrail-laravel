<?php

declare(strict_types=1);

namespace LedgerTrail;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LedgerTrail\Jobs\SendAuditEvent;
use LedgerTrail\Scanners\PiiScanner;

final class AuditTrailManager
{
    public function log(string $eventType, array $metadata = [], array $options = []): void
    {
        $tenantId = $this->resolveTenantId();
        $actorId  = $options['actor_id']   ?? $this->resolveActorId();
        $actorType = $options['actor_type'] ?? 'user';

        $metadata = $this->redact($metadata);

        $piiFindings = PiiScanner::scan($actorId, $metadata);
        if ($piiFindings !== []) {
            Log::warning('LEDGERTRAIL_PII_DETECTED', [
                'event_type' => $eventType,
                'fields'     => array_column($piiFindings, 'field'),
            ]);
            $metadata['_pii_warning'] = true;
        }

        $payload = array_filter([
            'event_type'    => $eventType,
            'actor_id'      => $actorId,
            'actor_type'    => $actorType,
            'target_id'     => $options['target_id']     ?? null,
            'target_type'   => $options['target_type']   ?? null,
            'sub_tenant_id' => $options['sub_tenant_id'] ?? null,
            'occurred_at'   => $options['occurred_at']   ?? null,
            'metadata'      => $metadata,
        ], fn ($v) => $v !== null);

        if (config('ledgertrail.async', true)) {
            dispatch(new SendAuditEvent($payload))->onQueue(config('ledgertrail.queue', 'ledgertrail'));
            return;
        }

        $response = Http::withToken(config('ledgertrail.api_key'))
            ->post(config('ledgertrail.api_url') . '/api/v1/events', $payload);

        if ($response->failed()) {
            throw new \RuntimeException('LedgerTrail: HTTP ' . $response->status() . ' — ' . $response->body());
        }
    }

    private function resolveTenantId(): string
    {
        $resolver = config('ledgertrail.tenant_resolver');
        if (is_callable($resolver)) {
            $id = $resolver();
            if ($id !== null) {
                return (string) $id;
            }
        }

        if (Auth::check()) {
            $user = Auth::user();
            if (isset($user->tenant_id) && $user->tenant_id !== null) {
                return (string) $user->tenant_id;
            }
        }

        throw new \RuntimeException('LedgerTrail: cannot resolve tenant_id — set tenant_resolver in config or authenticate a user with tenant_id');
    }

    private function resolveActorId(): string
    {
        if (Auth::check()) {
            $user = Auth::user();
            return (string) ($user->getAuthIdentifier() ?? 'anonymous');
        }
        return 'anonymous';
    }

    private function redact(array $data): array
    {
        $keys = config('ledgertrail.redact', []);

        foreach ($data as $k => $v) {
            if (in_array($k, $keys, true)) {
                unset($data[$k]);
            } elseif (is_array($v)) {
                $data[$k] = $this->redact($v);
            }
        }

        return $data;
    }
}
