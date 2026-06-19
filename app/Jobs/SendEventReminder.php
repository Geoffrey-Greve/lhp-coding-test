<?php

namespace App\Jobs;

use App\Mail\EventReminder;
use App\Models\EventAttendee;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendEventReminder implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EventAttendee $attendee,
        public string $reminderLabel,
        public string $reminderColumn,
    ) {}

    public function handle(): void
    {
        $this->attendee->load('event');

        Mail::to($this->attendee->email)->send(
            new EventReminder($this->attendee, $this->reminderLabel),
        );

        $this->attendee->update([$this->reminderColumn => now()]);
    }
}
