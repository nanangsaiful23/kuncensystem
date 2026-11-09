<?php

namespace App\Models\Concerns;

use DateTimeInterface;

/**
 * Laravel 7+ serializes dates in toArray()/toJson() as ISO-8601 UTC
 * (e.g. "2026-11-09T06:00:00.000000Z"). Laravel 6 used "Y-m-d H:i:s" in the
 * app timezone. The views' JavaScript reads the old format, so keep it.
 */
trait SerializesDatesLegacy
{
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
