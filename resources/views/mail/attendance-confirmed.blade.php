<x-mail::message>
# You're on the list!

Hi {{ $attendeeName }},

Thanks for registering for **{{ $eventTitle }}**.

**When:** {{ $eventSchedule }}

**Where:** {{ $eventLocation }}

<x-mail::button :url="$eventUrl">
View event
</x-mail::button>

See you there!
</x-mail::message>
