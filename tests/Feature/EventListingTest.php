<?php

use App\Jobs\SendAttendanceConfirmation;
use App\Jobs\SendEventReminder;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('renders the events listing shell without authentication', function () {
    $this->get(route('events.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Index')
            ->has('statuses', 4)
            ->where('filters.from', '2023-01-01')
        );
});

it('returns a json page of events with load stats for lazy loading', function () {
    $user = User::factory()->create(['name' => 'Ada Lovelace']);
    Event::factory()->for($user)->create([
        'type' => 'concert',
        'status' => 'published',
        'created_time' => 1_700_000_000,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
    ]);

    $this->getJson(route('events.data'))
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'total',
            'stats' => ['ms', 'bytes'],
        ])
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.type', 'concert')
        ->assertJsonPath('data.0.created_time', 1_700_000_000)
        ->assertJsonPath('data.0.latitude', 40.7128)
        ->assertJsonPath('data.0.user.name', 'Ada Lovelace');
});

it('filters the data endpoint by status', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['status' => 'published']);
    Event::factory()->for($user)->create(['status' => 'cancelled']);

    $this->getJson(route('events.data', ['status' => 'cancelled']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.status', 'cancelled');
});

it('shows an event detail page with formatted visual data', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'status' => 'published',
        'payload' => [
            'name' => 'Global Tech Summit',
            'description' => 'A great summit.',
            'venue' => ['name' => 'Grand Hall'],
            'schedule' => ['starts_at' => 1_700_000_000, 'ends_at' => 1_700_007_200],
        ],
        'latitude' => 40.7128,
        'longitude' => -74.0060,
    ]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Show')
            ->where('event.id', $event->id)
            ->where('event.title', 'Global Tech Summit')
            ->has('event.images', 2)
        );
});

it('renders the two visualization pages and the dashboard without authentication', function () {
    $this->get(route('events.visual1'))->assertOk();
    $this->get(route('events.visual2'))->assertOk();
    $this->get(route('dashboard'))->assertOk();
});

it('returns visual listing data with images and location', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => now()->addWeek()->timestamp,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'payload' => [
            'name' => 'Summer Jazz Night',
            'description' => 'Live music under the stars.',
            'venue' => ['name' => 'Riverside Gardens'],
            'schedule' => ['starts_at' => now()->addWeek()->timestamp, 'ends_at' => now()->addWeek()->addHours(2)->timestamp],
        ],
    ]);

    $this->getJson(route('events.visuals.data'))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.title', 'Summer Jazz Night')
        ->assertJsonCount(2, 'data.0.images')
        ->assertJsonPath('data.0.location', 'Riverside Gardens · New York, NY');
});

it('registers an attendee and queues a confirmation email', function () {
    Queue::fake();
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create(['status' => 'published']);

    $this->post(route('events.attendees.store', $event), [
        'name' => 'Taylor Swift',
        'email' => 'taylor@example.test',
    ])->assertRedirect();

    $this->assertDatabaseHas('event_attendees', [
        'event_id' => $event->id,
        'email' => 'taylor@example.test',
    ]);

    Queue::assertPushed(SendAttendanceConfirmation::class);
});

it('sends reminder emails for events in the reminder window', function () {
    Queue::fake();

    $user = User::factory()->create();
    $startsAt = now()->addDays(3)->addMinutes(30)->timestamp;

    $event = Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => $startsAt,
        'payload' => [
            'name' => 'Reminder Test Event',
            'description' => 'Soon!',
            'schedule' => ['starts_at' => $startsAt, 'ends_at' => $startsAt + 3600],
        ],
    ]);

    EventAttendee::create([
        'event_id' => $event->id,
        'name' => 'Pat',
        'email' => 'pat@example.test',
    ]);

    Artisan::call('events:send-reminders');

    Queue::assertPushed(SendEventReminder::class, function (SendEventReminder $job) {
        return $job->reminderLabel === '3 days'
            && $job->reminderColumn === 'reminder_3d_sent_at'
            && $job->attendee->email === 'pat@example.test';
    });
});

it('rejects registration for non-published events', function () {
    Queue::fake();
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create(['status' => 'cancelled']);

    $this->post(route('events.attendees.store', $event), [
        'name' => 'Taylor Swift',
        'email' => 'taylor@example.test',
    ])->assertRedirect();

    $this->assertDatabaseMissing('event_attendees', [
        'event_id' => $event->id,
        'email' => 'taylor@example.test',
    ]);

    Queue::assertNothingPushed();
});
