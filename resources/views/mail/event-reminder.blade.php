<x-mail::message>
# {{ $reminderLabel }} to go

Hi {{ $attendeeName }},

This is a friendly reminder that **{{ $eventTitle }}** is coming up in **{{ $reminderLabel }}**.

**When:** {{ $eventSchedule }}

**Where:** {{ $eventLocation }}

<x-mail::button :url="$eventUrl">
View event details
</x-mail::button>

We look forward to seeing you!
</x-mail::message>
