<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventImage;
use Illuminate\Support\Facades\DB;

class EventImageAssigner
{
    /**
     * Persist two placeholder image rows for an event.
     */
    public function assignForEvent(Event $event): void
    {
        if ($event->images()->exists()) {
            return;
        }

        $formatter = app(EventFormatter::class);
        $images = $formatter->placeholderImagesFor($event->id);

        foreach ($images as $index => $image) {
            EventImage::create([
                'event_id' => $event->id,
                'path' => $image['path'],
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * Bulk-assign images for events missing them (chunked for large datasets).
     */
    public function assignMissing(int $chunkSize = 2000): int
    {
        $placeholders = config('cities.placeholders', []);
        if (count($placeholders) < 2) {
            return 0;
        }

        $assigned = 0;
        $now = now();

        Event::query()
            ->whereDoesntHave('images')
            ->select(['id'])
            ->orderBy('id')
            ->chunkById($chunkSize, function ($events) use ($placeholders, $now, &$assigned) {
                $rows = [];

                foreach ($events as $event) {
                    $hash = crc32($event->id);
                    $paths = [
                        $placeholders[$hash % count($placeholders)],
                        $placeholders[($hash + 1) % count($placeholders)],
                    ];

                    foreach ($paths as $sortOrder => $path) {
                        $rows[] = [
                            'event_id' => $event->id,
                            'path' => $path,
                            'sort_order' => $sortOrder,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($rows !== []) {
                    DB::table('event_images')->insert($rows);
                    $assigned += count($events);
                }
            }, column: 'id');

        return $assigned;
    }
}
