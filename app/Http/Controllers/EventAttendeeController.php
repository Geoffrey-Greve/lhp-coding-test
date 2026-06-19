<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventVisualResource;
use App\Jobs\SendAttendanceConfirmation;
use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventAttendeeController extends Controller
{
    public function store(Request $request, Event $event): RedirectResponse
    {
        if ($event->status !== 'published') {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Registration is only open for published events.',
            ]);

            return back();
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $attendee = EventAttendee::firstOrCreate(
            [
                'event_id' => $event->id,
                'email' => strtolower($validated['email']),
            ],
            ['name' => $validated['name']],
        );

        if ($attendee->wasRecentlyCreated) {
            SendAttendanceConfirmation::dispatch($attendee);
        }

        $message = $attendee->wasRecentlyCreated
            ? "You're on the list! Check your inbox for confirmation."
            : "You're already registered for this event.";

        Inertia::flash('toast', [
            'type' => $attendee->wasRecentlyCreated ? 'success' : 'info',
            'message' => $message,
        ]);

        return back();
    }

    public function show(Event $event): Response
    {
        $event->load(['user', 'images', 'attendees' => fn ($q) => $q->latest()->limit(20)]);
        $event->loadCount('attendees');

        return Inertia::render('Events/Show', [
            'event' => (new EventVisualResource($event))->resolve(),
            'attendees' => $event->attendees->map(fn (EventAttendee $a) => [
                'id' => $a->id,
                'name' => $a->name,
                'email' => $this->maskEmail($a->email),
                'registered_at' => $a->created_at?->toIso8601String(),
            ]),
        ]);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, min(2, strlen($local)));

        return "{$visible}***@{$domain}";
    }
}
