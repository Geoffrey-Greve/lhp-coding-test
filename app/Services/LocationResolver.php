<?php

namespace App\Services;

class LocationResolver
{
    /** @var array<int, array{slug: string, name: string, lat: float, lng: float, timezone: string}>|null */
    private static ?array $anchors = null;

    /**
     * @return array{slug: string, name: string, lat: float, lng: float, timezone: string}
     */
    public function nearestCity(float $latitude, float $longitude): array
    {
        $anchors = $this->anchors();
        $best = $anchors[0];
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($anchors as $anchor) {
            $distance = $this->distanceSquared($latitude, $longitude, $anchor['lat'], $anchor['lng']);
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $anchor;
            }
        }

        return $best;
    }

    public function formatLocation(float $latitude, float $longitude, ?string $venueName = null): string
    {
        $city = $this->nearestCity($latitude, $longitude);

        if ($venueName) {
            return "{$venueName} · {$city['name']}";
        }

        return $city['name'];
    }

    /**
     * @return array<int, array{slug: string, name: string}>
     */
    public function cityOptions(): array
    {
        return array_map(
            fn (array $anchor) => ['slug' => $anchor['slug'], 'name' => $anchor['name']],
            $this->anchors(),
        );
    }

  /**
     * @return array{slug: string, name: string, lat: float, lng: float, timezone: string}|null
     */
    public function findBySlug(?string $slug): ?array
    {
        if (! $slug) {
            return null;
        }

        foreach ($this->anchors() as $anchor) {
            if ($anchor['slug'] === $slug) {
                return $anchor;
            }
        }

        return null;
    }

    /**
     * Bounding box filter matching seeder jitter (~0.5°).
     *
     * @return array{min_lat: float, max_lat: float, min_lng: float, max_lng: float}|null
     */
    public function boundingBoxForSlug(?string $slug): ?array
    {
        $city = $this->findBySlug($slug);
        if (! $city) {
            return null;
        }

        $delta = 0.55;

        return [
            'min_lat' => $city['lat'] - $delta,
            'max_lat' => $city['lat'] + $delta,
            'min_lng' => $city['lng'] - $delta,
            'max_lng' => $city['lng'] + $delta,
        ];
    }

    /**
     * @return array<int, array{slug: string, name: string, lat: float, lng: float, timezone: string}>
     */
    private function anchors(): array
    {
        if (self::$anchors === null) {
            /** @var array<int, array{slug: string, name: string, lat: float, lng: float, timezone: string}> $anchors */
            $anchors = config('cities.anchors', []);
            self::$anchors = $anchors;
        }

        return self::$anchors;
    }

    private function distanceSquared(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dlat = $lat1 - $lat2;
        $dlng = $lng1 - $lng2;

        return ($dlat * $dlat) + ($dlng * $dlng);
    }
}
