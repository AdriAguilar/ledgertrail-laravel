<?php

declare(strict_types=1);

return [
    'api_key'         => env('LEDGERTRAIL_API_KEY'),
    'api_url'         => env('LEDGERTRAIL_API_URL', 'https://api.ledgertrail.io'),
    'async'           => env('LEDGERTRAIL_ASYNC', true),
    'queue'           => env('LEDGERTRAIL_QUEUE', 'ledgertrail'),
    'tenant_resolver' => null,
    'redact'          => ['password', 'token', 'secret', 'credit_card'],
];
