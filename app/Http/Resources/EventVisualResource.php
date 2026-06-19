<?php

namespace App\Http\Resources;

use App\Models\Event;
use App\Services\EventFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Event */
class EventVisualResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var EventFormatter $formatter */
        $formatter = app(EventFormatter::class);
        $schedule = $formatter->formatSchedule($this->resource);

        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'title' => $formatter->title($this->resource),
            'description' => $formatter->description($this->resource),
            'location' => $formatter->locationLabel($this->resource),
            'city_slug' => $formatter->citySlug($this->resource),
            'venue' => $formatter->venueName($this->resource),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'starts_at' => $formatter->startsAt($this->resource),
            'ends_at' => $formatter->endsAt($this->resource),
            'schedule' => $schedule,
            'images' => $formatter->images($this->resource),
            'attendee_count' => $this->whenCounted('attendees'),
        ];
    }
}
