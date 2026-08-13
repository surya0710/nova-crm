<?php

namespace Tests\Unit\Hrms;

use App\Services\Hrms\GeofenceService;
use PHPUnit\Framework\TestCase;

class GeofenceServiceDistanceTest extends TestCase
{
    public function test_distance_meters_is_zero_for_identical_points(): void
    {
        $service = new GeofenceService;
        $distance = $service->distanceMeters(12.9716, 77.5946, 12.9716, 77.5946);

        $this->assertSame(0.0, round($distance, 6));
    }

    public function test_distance_meters_detects_points_about_one_hundred_meters_apart(): void
    {
        $service = new GeofenceService;

        // ~111m north of equator-ish latitude using ~0.001 degree latitude.
        $distance = $service->distanceMeters(12.9716000, 77.5946000, 12.9726000, 77.5946000);

        $this->assertGreaterThan(100, $distance);
        $this->assertLessThan(120, $distance);
    }
}
