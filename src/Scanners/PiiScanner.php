<?php

declare(strict_types=1);

namespace LedgerTrail\Scanners;

final class PiiScanner
{
    private const PATTERNS = [
        'email'       => '/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i',
        'phone'       => '/\+[1-9]\d{6,14}/',
        'national_id' => '/\b\d{8}[A-HJ-NP-TV-Z]\b|\b[XYZ]\d{7}[A-HJ-NP-TV-Z]\b/i',
    ];

    /**
     * @return array<int, array{field: string, type: string}>
     */
    public static function scan(string $actorId, array $metadata): array
    {
        $findings = [];

        self::scanValue($actorId, 'actor_id', $findings);
        self::scanArray($metadata, 'metadata', $findings);

        return $findings;
    }

    private static function scanValue(string $value, string $field, array &$findings): void
    {
        foreach (self::PATTERNS as $type => $pattern) {
            if (preg_match($pattern, $value)) {
                $findings[] = ['field' => $field, 'type' => $type];
                return;
            }
        }
    }

    private static function scanArray(array $data, string $prefix, array &$findings): void
    {
        foreach ($data as $key => $value) {
            $path = $prefix . '.' . $key;

            if (is_string($value)) {
                self::scanValue($value, $path, $findings);
            } elseif (is_array($value)) {
                self::scanArray($value, $path, $findings);
            }
        }
    }
}
