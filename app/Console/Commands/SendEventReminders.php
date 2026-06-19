<?php

namespace App\Console\Commands;

use App\Mail\EventReminder;
use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Send 3-day and 24-hour reminder emails for upcoming events';

    public function handle(): int
    {
        $this->sendReminders(
            column: 'reminder_3d_sent_at',
            label: '3 days',
            windowStart: now()->addDays(3),
            windowEnd: now()->addDays(3)->addHour(),
        );

        $this->sendReminders(
            column: 'reminder_24h_sent_at',
            label: '24 hours',
            windowStart: now()->addDay(),
            windowEnd: now()->addDay()->addHour(),
        );

        return self::SUCCESS;
    }

    private function sendReminders(
        string $column,
        string $label,
        \Illuminate\Support\Carbon $windowStart,
        \Illuminate\Support\Carbon $windowEnd,
    ): void {
        $startTs = $windowStart->timestamp;
        $endTs = $windowEnd->timestamp;

        $eventIds = Event::query()
            ->where('status', 'published')
            ->whereBetween('created_time', [$startTs, $endTs])
            ->pluck('id');

        if ($eventIds->isEmpty()) {
            return;
        }

        EventAttendee::query()
            ->whereIn('event_id', $eventIds)
            ->whereNull($column)
            ->with('event')
            ->chunkById(200, function ($attendees) use ($column, $label) {
                foreach ($attendees as $attendee) {
                    Mail::to($attendee->email)->queue(new EventReminder($attendee, $label));
                    $attendee->update([$column => now()]);
                }
            });
    }
}
