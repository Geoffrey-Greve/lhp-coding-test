# Implementation Decisions

## Layouts
- **Visual 1 (Gallery):** Responsive card grid with hover image carousel — optimized for browsing many events at a glance.
- **Visual 2 (Timeline):** Vertical chronological schedule grouped by day, alternating sides on desktop — a different mental model from the grid.

## Location
Events only store lat/lng. City names come from the same anchor list used by `EventSeeder`, via nearest-neighbor lookup. Venue names are taken from `payload.venue.name`. Location filtering uses a bounding box (~0.55°) around the selected anchor, matching seeder jitter.

## Date & time
Timestamps are stored as UTC Unix times (`created_time` / `payload.schedule.starts_at`). Display uses the nearest city's IANA timezone for the primary label, with the viewer's local timezone appended when it differs.

## Images
Four local SVG placeholders live in `public/images/events/`. Each event gets two images, assigned deterministically by event ID (via `event_images` rows for factory/test data, or computed paths at read time). Run `php artisan events:assign-images` to persist rows for the full seeded dataset.

## Performance
Visual listings default to `published` events only, paginate 24 per page, and use a composite index on `(status, created_time)`. Payload formatting happens in `EventVisualResource` per page, not for the entire dataset.

## Attendees & email
Registration is open for published events only (no auth). Confirmation emails are queued on signup. Reminders run hourly via `events:send-reminders`, using `EventFormatter::startsAt()` with catch-up windows (3-day reminders for events 1–3 days out; 24-hour reminders within the next day). Reminder flags are set inside `SendEventReminder` after the email sends. Mail defaults to the `log` driver locally — check `storage/logs/laravel.log`.
