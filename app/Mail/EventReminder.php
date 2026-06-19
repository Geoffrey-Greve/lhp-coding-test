<?php

namespace App\Mail;

use App\Models\EventAttendee;
use App\Services\EventFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EventAttendee $attendee,
        public string $reminderLabel,
    ) {}

    public function envelope(): Envelope
    {
        $event = $this->attendee->event;
        $title = app(EventFormatter::class)->title($event);

        return new Envelope(
            subject: "Reminder ({$this->reminderLabel}) — {$title}",
        );
    }

    public function content(): Content
    {
        $event = $this->attendee->event;
        $formatter = app(EventFormatter::class);

        return new Content(
            markdown: 'mail.event-reminder',
            with: [
                'attendeeName' => $this->attendee->name,
                'reminderLabel' => $this->reminderLabel,
                'eventTitle' => $formatter->title($event),
                'eventLocation' => $formatter->locationLabel($event),
                'eventSchedule' => $formatter->formatSchedule($event)['event'],
                'eventUrl' => url("/events/{$event->id}"),
            ],
        );
    }
}
