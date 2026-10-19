<?php
namespace App\Support;

final class GoStatus
{
    public const FLOWS = [
        'JP' => ['CO Web', 'Arrived JP/KR', 'Shipping ID', 'Arrived INA', 'Arrived Admin'],
        'KR' => ['CO Web', 'Arrived JP/KR', 'Shipping ID', 'Arrived INA', 'Arrived Admin'],
        'ID' => ['Need to CO', 'Otw Admin', 'Arrived Admin'],
    ];

    public const LEGACY_LABELS = [
        'OTW INA' => 'Shipping ID',
        'Arrived Ina' => 'Arrived INA',
        'Arrived admin' => 'Arrived Admin',
    ];

    public static function options(string $origin): array { return self::FLOWS[$origin] ?? []; }
    public static function initial(string $origin): string { return self::options($origin)[0] ?? ''; }
    public static function label(?string $status): string {
        return self::LEGACY_LABELS[$status ?? ''] ?? ($status ?? '');
    }
    public static function normalizedCode(string $input): string { return strtoupper(preg_replace('/\s+/', ' ', trim($input))); }
    public static function parseOrigin(string $code): ?string {
        return preg_match('/^(JP|KR|ID) [1-9][0-9]*$/', self::normalizedCode($code), $match) ? $match[1] : null;
    }
}
