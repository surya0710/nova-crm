<?php

namespace App\Services\Hrms;

use App\Models\AttendanceGeofence;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GeofenceService
{
    public function create(array $data, ?\App\Models\User $actor = null): AttendanceGeofence
    {
        return DB::transaction(function () use ($data): AttendanceGeofence {
            $this->assertValidCoordinates((float) $data['latitude'], (float) $data['longitude']);
            $this->assertValidRadius((int) $data['radius_meters']);
            $this->assertEffectiveRange($data['effective_from'] ?? null, $data['effective_to'] ?? null);

            return AttendanceGeofence::query()->create([
                'branch_id' => $data['branch_id'] ?? null,
                'name' => $data['name'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'radius_meters' => $data['radius_meters'],
                'is_active' => (bool) ($data['is_active'] ?? true),
                'effective_from' => $data['effective_from'] ?? null,
                'effective_to' => $data['effective_to'] ?? null,
            ]);
        });
    }

    public function update(AttendanceGeofence $geofence, array $data): AttendanceGeofence
    {
        return DB::transaction(function () use ($geofence, $data): AttendanceGeofence {
            if (isset($data['latitude'], $data['longitude'])) {
                $this->assertValidCoordinates((float) $data['latitude'], (float) $data['longitude']);
            }
            if (isset($data['radius_meters'])) {
                $this->assertValidRadius((int) $data['radius_meters']);
            }
            $this->assertEffectiveRange(
                $data['effective_from'] ?? $geofence->effective_from?->toDateString(),
                $data['effective_to'] ?? $geofence->effective_to?->toDateString(),
            );

            $geofence->update($data);

            return $geofence->fresh(['branch']);
        });
    }

    public function delete(AttendanceGeofence $geofence): void
    {
        $geofence->delete();
    }

    /**
     * Resolve applicable geofences for an employee on a date.
     * Prefer branch-specific fences; fall back to organization-wide.
     *
     * @return Collection<int, AttendanceGeofence>
     */
    public function resolveApplicableGeofences(Employee $employee, Carbon|string|null $date = null): Collection
    {
        $day = $date === null ? now()->startOfDay() : Carbon::parse($date)->startOfDay();
        $branchId = $employee->branch_id;

        $query = AttendanceGeofence::query()
            ->where('is_active', true)
            ->where(function ($builder) use ($day) {
                $builder->whereNull('effective_from')
                    ->orWhereDate('effective_from', '<=', $day->toDateString());
            })
            ->where(function ($builder) use ($day) {
                $builder->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $day->toDateString());
            });

        if ($branchId !== null) {
            $branchScoped = (clone $query)
                ->where('branch_id', $branchId)
                ->orderBy('name')
                ->get();

            if ($branchScoped->isNotEmpty()) {
                return $branchScoped;
            }
        }

        return $query
            ->whereNull('branch_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{
     *     inside: bool,
     *     geofence: ?AttendanceGeofence,
     *     distance_meters: ?float,
     *     reason: ?string,
     *     candidates: list<array{geofence_id:int,name:string,distance_meters:float,radius_meters:int,inside:bool}>
     * }
     */
    public function validateCoordinates(
        Employee $employee,
        float $latitude,
        float $longitude,
        Carbon|string|null $date = null,
    ): array {
        $this->assertValidCoordinates($latitude, $longitude);

        $geofences = $this->resolveApplicableGeofences($employee, $date);

        if ($geofences->isEmpty()) {
            return [
                'inside' => false,
                'geofence' => null,
                'distance_meters' => null,
                'reason' => 'no_applicable_geofence',
                'candidates' => [],
            ];
        }

        $candidates = [];
        $bestInside = null;
        $bestInsideDistance = null;

        foreach ($geofences as $geofence) {
            $distance = $this->distanceMeters(
                $latitude,
                $longitude,
                (float) $geofence->latitude,
                (float) $geofence->longitude,
            );
            $inside = $distance <= (float) $geofence->radius_meters;

            $candidates[] = [
                'geofence_id' => $geofence->id,
                'name' => $geofence->name,
                'distance_meters' => round($distance, 2),
                'radius_meters' => (int) $geofence->radius_meters,
                'inside' => $inside,
            ];

            if ($inside && ($bestInsideDistance === null || $distance < $bestInsideDistance)) {
                $bestInside = $geofence;
                $bestInsideDistance = $distance;
            }
        }

        if ($bestInside !== null) {
            return [
                'inside' => true,
                'geofence' => $bestInside,
                'distance_meters' => round((float) $bestInsideDistance, 2),
                'reason' => null,
                'candidates' => $candidates,
            ];
        }

        usort($candidates, fn (array $a, array $b) => $a['distance_meters'] <=> $b['distance_meters']);

        return [
            'inside' => false,
            'geofence' => null,
            'distance_meters' => $candidates[0]['distance_meters'] ?? null,
            'reason' => 'outside_geofence',
            'candidates' => $candidates,
        ];
    }

    public function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000.0;
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }

    public function assertValidCoordinates(float $latitude, float $longitude): void
    {
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw ValidationException::withMessages([
                'latitude' => __('Coordinates are outside the valid latitude/longitude range.'),
            ]);
        }
    }

    public function assertValidRadius(int $radiusMeters): void
    {
        $min = (int) config('hrms.attendance_geofence.min_radius_meters', 10);
        $max = (int) config('hrms.attendance_geofence.max_radius_meters', 50000);

        if ($radiusMeters < $min || $radiusMeters > $max) {
            throw ValidationException::withMessages([
                'radius_meters' => __('Geofence radius must be between :min and :max meters.', [
                    'min' => $min,
                    'max' => $max,
                ]),
            ]);
        }
    }

    protected function assertEffectiveRange(mixed $from, mixed $to): void
    {
        if ($from === null || $to === null || $from === '' || $to === '') {
            return;
        }

        if (Carbon::parse($to)->lt(Carbon::parse($from))) {
            throw ValidationException::withMessages([
                'effective_to' => __('Effective until must be on or after effective from.'),
            ]);
        }
    }
}
