<?php
namespace App\Support;

use InvalidArgumentException;

final class PriceCalculator
{
    public const ORDER_TYPES = [
        'personal' => 'Personal Order',
        'sharing' => 'Request Sharing',
    ];

    /** Rupiah amount. The service fee is applied once per calculation, not per item. */
    public static function calculate(float $price, float $rate, float $fee, bool $rounding, int $increment): float
    {
        if (!is_finite($price) || !is_finite($rate) || !is_finite($fee)
            || $price < 0 || $rate <= 0 || $fee < 0 || $increment < 1) {
            throw new InvalidArgumentException('Parameter kalkulator tidak valid.');
        }
        $subtotal = round($price * $rate, 2);
        $rawTotal = round($subtotal + $fee, 2);
        // Keep integer conversions / multiplication safely within signed 64-bit PHP.
        if (!is_finite($rawTotal) || $rawTotal > 1000000000000) {
            throw new InvalidArgumentException('Nominal terlalu besar untuk dihitung.');
        }
        if (!$rounding) return round($rawTotal); // Existing calculator rounds to integer Rupiah.
        // A tiny epsilon avoids double rounding to the next increment on binary-float boundaries.
        return (float) (ceil(round($rawTotal / $increment, 8)) * $increment);
    }
}
