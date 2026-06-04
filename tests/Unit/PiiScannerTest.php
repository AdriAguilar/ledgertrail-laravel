<?php

declare(strict_types=1);

use LedgerTrail\Scanners\PiiScanner;

it('detects email in actor_id', function () {
    $findings = PiiScanner::scan('user@example.com', []);
    expect($findings)->toHaveCount(1);
    expect($findings[0]['field'])->toBe('actor_id');
    expect($findings[0]['type'])->toBe('email');
});

it('detects DNI in metadata', function () {
    $findings = PiiScanner::scan('u1', ['document' => '12345678Z']);
    expect($findings)->toHaveCount(1);
    expect($findings[0]['field'])->toBe('metadata.document');
    expect($findings[0]['type'])->toBe('national_id');
});

it('returns empty array for clean data', function () {
    $findings = PiiScanner::scan('user-123', ['action' => 'button_click', 'page' => '/home']);
    expect($findings)->toBeEmpty();
});

it('scans nested metadata recursively', function () {
    $findings = PiiScanner::scan('u1', ['user' => ['contact' => 'nested@example.com']]);
    expect($findings)->toHaveCount(1);
    expect($findings[0]['field'])->toBe('metadata.user.contact');
    expect($findings[0]['type'])->toBe('email');
});
