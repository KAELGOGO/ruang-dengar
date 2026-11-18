<?php

namespace Tests\Unit;

use App\Support\AgeTier;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class AgeTierTest extends TestCase
{
    public function test_tier_boundaries(): void
    {
        $on = Carbon::parse('2026-11-19');

        $this->assertNull(AgeTier::of(Carbon::parse('2013-11-20'), $on)); // 12, one day short of 13
        $this->assertSame(AgeTier::TEEN, AgeTier::of(Carbon::parse('2013-11-19'), $on)); // 13 today
        $this->assertSame(AgeTier::TEEN, AgeTier::of(Carbon::parse('2008-11-20'), $on)); // 17
        $this->assertSame(AgeTier::YOUNG_ADULT, AgeTier::of(Carbon::parse('2008-11-19'), $on)); // 18 today
        $this->assertSame(AgeTier::YOUNG_ADULT, AgeTier::of(Carbon::parse('2001-11-20'), $on)); // 24
        $this->assertSame(AgeTier::ADULT, AgeTier::of(Carbon::parse('2001-11-19'), $on)); // 25 today
    }

    public function test_birth_date_range_matches_the_tier(): void
    {
        $on = Carbon::parse('2026-11-19');

        $this->assertSame(['2008-11-20', '2013-11-19'], AgeTier::birthDateRange(AgeTier::TEEN, $on));
        $this->assertSame(['2001-11-20', '2008-11-19'], AgeTier::birthDateRange(AgeTier::YOUNG_ADULT, $on));
        $this->assertSame([null, '2001-11-19'], AgeTier::birthDateRange(AgeTier::ADULT, $on));
    }
}
