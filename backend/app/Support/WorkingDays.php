<?php

namespace App\Support;

use Carbon\CarbonInterface;

class WorkingDays
{
    // PRD 9.1.2/10.3: SLA duration is in working days, excluding weekends and
    // an admin-configured Ghana public holidays calendar. The holidays
    // calendar admin UI isn't built in this v1 slice, so this only skips
    // weekends - swap in a real holiday list once that config exists.
    public static function addTo(CarbonInterface $start, int $days): CarbonInterface
    {
        $date = $start->copy();
        while ($days > 0) {
            $date = $date->addDay();
            if (! $date->isWeekend()) {
                $days--;
            }
        }

        return $date;
    }
}
