<?php

namespace App\Console\Commands;

use App\Jobs\SendEventReminder;
use App\Models\EventAttendee;
use App\Services\EventFormatter;
use Illuminate\Console\Command;

class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Send 3-day and 24-hour reminder emails for upcoming events';

    public function handle(EventFormatter $formatter): int
    {
        $this->sendReminders(
            column: 'reminder_3d_sent_at',
            label: '3 days',
            minLeadSeconds: 86400,
            maxLeadSeconds: 3 * 86400,
            $formatter,
        );

        $this->sendReminders(
            column: 'reminder_24h_sent_at',
            label: '24 hours',
            minLeadSeconds: 0,
            maxLeadSeconds: 86400,
            $formatter,
        );

        return self::SUCCESS;
    }

    /**
     * Send reminders for events starting within (minLead, maxLead] from now
     * that have not yet received this reminder. Catches up if cron was missed.
     */
    private function sendReminders(
        string $column,
        string $label,
        int $minLeadSeconds,
        int $maxLeadSeconds,
        EventFormatter $formatter,
    ): void {
        $now = now()->timestamp;
        $minStart = $now + $minLeadSeconds;
        $maxStart = $now + $maxLeadSeconds;

        EventAttendee::query()
            ->whereNull($column)
            ->whereHas('event', function ($query) use ($minStart, $maxStart) {
                $query->where('status', 'published')
                    ->where('created_time', '>', $minStart)
                    ->where('created_time', '<=', $maxStart);
            })
            ->with('event')
            ->chunkById(200, function ($attendees) use ($column, $label, $minStart, $maxStart, $now, $formatter) {
                foreach ($attendees as $attendee) {
                    $startsAt = $formatter->startsAt($attendee->event);

                    if ($startsAt === null || $startsAt <= $now) {
                        continue;
                    }

                    if ($startsAt <= $minStart || $startsAt > $maxStart) {
                        continue;
                    }

                    SendEventReminder::dispatch($attendee, $label, $column);
                }
            });
    }
}
