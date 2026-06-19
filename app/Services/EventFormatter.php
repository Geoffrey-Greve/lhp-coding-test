<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;

class EventFormatter
{
    public function __construct(
        private LocationResolver $locationResolver,
    ) {}

    public function startsAt(Event $event): ?int
    {
        $payload = $event->payload;
        $fromPayload = data_get($payload, 'schedule.starts_at');

        if (is_numeric($fromPayload)) {
            return (int) $fromPayload;
        }

        return $event->created_time !== null ? (int) $event->created_time : null;
    }

    public function endsAt(Event $event): ?int
    {
        $fromPayload = data_get($event->payload, 'schedule.ends_at');

        if (is_numeric($fromPayload)) {
            return (int) $fromPayload;
        }

        $starts = $this->startsAt($event);

        return $starts !== null ? $starts + 7200 : null;
    }

    /**
     * @return array{event: string, local: string, timezone: string, iso: string|null}
     */
    public function formatSchedule(Event $event): array
    {
        $startsAt = $this->startsAt($event);
        if ($startsAt === null) {
            return [
                'event' => 'Date TBA',
                'local' => '',
                'timezone' => 'UTC',
                'iso' => null,
            ];
        }

        $lat = $event->latitude ?? 0.0;
        $lng = $event->longitude ?? 0.0;
        $city = $this->locationResolver->nearestCity($lat, $lng);
        $timezone = $city['timezone'];

        $eventTime = Carbon::createFromTimestampUTC($startsAt)->setTimezone($timezone);
        $viewerTime = Carbon::createFromTimestampUTC($startsAt)->setTimezone($this->viewerTimezone());

        $eventFormatted = $eventTime->isoFormat('ddd, MMM D · h:mm A z');
        $localFormatted = $viewerTime->isoFormat('ddd, MMM D · h:mm A');

        $localSuffix = $eventTime->format('T') === $viewerTime->format('T')
            ? ''
            : " (your time: {$localFormatted})";

        return [
            'event' => $eventFormatted.$localSuffix,
            'local' => $localFormatted,
            'timezone' => $timezone,
            'iso' => $eventTime->toIso8601String(),
        ];
    }

    public function title(Event $event): string
    {
        return (string) (data_get($event->payload, 'name') ?: 'Untitled Event');
    }

    public function description(Event $event): string
    {
        return (string) (data_get($event->payload, 'description') ?: '');
    }

    public function venueName(Event $event): ?string
    {
        $venue = data_get($event->payload, 'venue.name');

        return is_string($venue) && $venue !== '' ? $venue : null;
    }

    public function locationLabel(Event $event): string
    {
        if ($event->latitude === null || $event->longitude === null) {
            return 'Location TBA';
        }

        return $this->locationResolver->formatLocation(
            $event->latitude,
            $event->longitude,
            $this->venueName($event),
        );
    }

    public function citySlug(Event $event): ?string
    {
        if ($event->latitude === null || $event->longitude === null) {
            return null;
        }

        return $this->locationResolver->nearestCity($event->latitude, $event->longitude)['slug'];
    }

    /**
     * @return array<int, array{url: string, path: string}>
     */
    public function images(Event $event): array
    {
        if ($event->relationLoaded('images') && $event->images->isNotEmpty()) {
            return $event->images
                ->sortBy('sort_order')
                ->values()
                ->map(fn ($image) => [
                    'url' => '/'.ltrim($image->path, '/'),
                    'path' => $image->path,
                ])
                ->all();
        }

        return $this->placeholderImagesFor($event->id);
    }

    /**
     * @return array<int, array{url: string, path: string}>
     */
    public function placeholderImagesFor(string $eventId): array
    {
        $placeholders = config('cities.placeholders', []);
        if (count($placeholders) < 2) {
            return [];
        }

        $hash = crc32($eventId);
        $first = $placeholders[$hash % count($placeholders)];
        $second = $placeholders[($hash + 1) % count($placeholders)];

        return [
            ['url' => '/'.$first, 'path' => $first],
            ['url' => '/'.$second, 'path' => $second],
        ];
    }

    private function viewerTimezone(): string
    {
        return config('app.display_timezone', date_default_timezone_get() ?: 'UTC');
    }
}
