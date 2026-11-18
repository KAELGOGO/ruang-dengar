<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Age tiers for safe matching. Always computed from the birth date, never stored.
 */
class AgeTier
{
    public const TEEN = 'teen';
    public const YOUNG_ADULT = 'young_adult';
    public const ADULT = 'adult';

    public const MIN_USER_AGE = 13;
    public const MIN_LISTENER_AGE = 18;

    public const LABELS = [
        self::TEEN => 'Remaja (13-17)',
        self::YOUNG_ADULT => 'Dewasa muda (18-24)',
        self::ADULT => 'Dewasa (25+)',
    ];

    // Inclusive [min, max] age per tier; null means no upper bound.
    private const RANGES = [
        self::TEEN => [13, 17],
        self::YOUNG_ADULT => [18, 24],
        self::ADULT => [25, null],
    ];

    public static function age(CarbonInterface $birthDate, ?CarbonInterface $on = null): int
    {
        return (int) floor($birthDate->diffInYears($on ?? now()));
    }

    /** Null when younger than the youngest tier. */
    public static function of(CarbonInterface $birthDate, ?CarbonInterface $on = null): ?string
    {
        $age = self::age($birthDate, $on);
        foreach (self::RANGES as $tier => [$min, $max]) {
            if ($age >= $min && ($max === null || $age <= $max)) {
                return $tier;
            }
        }

        return null;
    }

    /**
     * Birth dates that fall in $tier on $on, as [earliest, latest] date strings for queries.
     * earliest is null for the open-ended tier.
     */
    public static function birthDateRange(string $tier, CarbonInterface $on): array
    {
        [$min, $max] = self::RANGES[$tier];
        $latest = $on->copy()->subYears($min)->toDateString();
        $earliest = $max === null ? null : $on->copy()->subYears($max + 1)->addDay()->toDateString();

        return [$earliest, $latest];
    }
}
