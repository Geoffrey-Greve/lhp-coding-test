<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Calendar, MapPin } from '@lucide/vue';
import EventVisualShell from '@/components/events/EventVisualShell.vue';
import { Badge } from '@/components/ui/badge';
import type { CityOption, VisualEvent, VisualFilters } from '@/types/events';

defineProps<{
    filters: VisualFilters;
    cities: CityOption[];
}>();

function dayKey(event: VisualEvent): string {
    if (event.schedule.iso) {
        return event.schedule.iso.slice(0, 10);
    }

    if (!event.starts_at) {
        return 'unknown';
    }

    return new Date(event.starts_at * 1000).toISOString().slice(0, 10);
}

function dayLabel(key: string): string {
    if (key === 'unknown') {
return 'Date TBA';
}

    const date = new Date(`${key}T12:00:00`);

    return date.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
}

function groupEvents(events: VisualEvent[]) {
    const groups = new Map<string, VisualEvent[]>();

    for (const event of events) {
        const key = dayKey(event);
        const list = groups.get(key) ?? [];
        list.push(event);
        groups.set(key, list);
    }

    return [...groups.entries()].sort(([a], [b]) => a.localeCompare(b));
}
</script>

<template>
    <Head title="Events Visual 2" />

    <EventVisualShell
        :filters="filters"
        :cities="cities"
        title="Event Visuals — Timeline"
        subtitle="Follow events chronologically along a vertical schedule"
    >
        <template #content="{ events, loading, hasLoadedOnce, loadError }">
            <div v-if="events.length > 0" class="relative mx-auto max-w-3xl">
                <div class="absolute bottom-0 left-4 top-0 w-px bg-border md:left-1/2" />

                <div
                    v-for="([date, dayEvents], groupIndex) in groupEvents(events as VisualEvent[])"
                    :key="date"
                    class="relative mb-10 animate-in fade-in slide-in-from-bottom-3 fill-mode-both"
                    :style="{ animationDelay: `${groupIndex * 80}ms` }"
                >
                    <div
                        class="sticky top-4 z-10 mb-6 flex justify-start md:justify-center"
                    >
                        <span class="rounded-full border bg-background px-4 py-1.5 text-sm font-medium shadow-sm">
                            {{ dayLabel(date) }}
                        </span>
                    </div>

                    <div class="space-y-8">
                        <article
                            v-for="(event, index) in dayEvents"
                            :key="event.id"
                            class="relative pl-10 md:pl-0"
                            :class="index % 2 === 0 ? 'md:pr-[calc(50%+2rem)] md:text-right' : 'md:pl-[calc(50%+2rem)]'"
                        >
                            <span
                                class="absolute left-2.5 top-6 size-3 rounded-full border-2 border-primary bg-background md:left-1/2 md:-translate-x-1/2"
                            />

                            <div
                                class="rounded-xl border bg-card p-4 shadow-sm transition-all duration-300 hover:border-primary/40 hover:shadow-md"
                                :class="index % 2 === 0 ? 'md:ml-auto' : ''"
                            >
                                <div class="mb-3 flex flex-wrap items-center gap-2" :class="index % 2 === 0 ? 'md:justify-end' : ''">
                                    <Badge variant="outline" class="capitalize">{{ event.type }}</Badge>
                                    <span class="text-xs text-muted-foreground">{{ event.schedule.event }}</span>
                                </div>

                                <div class="mb-3 flex gap-2 overflow-hidden rounded-lg" :class="index % 2 === 0 ? 'md:flex-row-reverse' : ''">
                                    <img
                                        v-for="(image, imgIndex) in event.images.slice(0, 2)"
                                        :key="imgIndex"
                                        :src="image.url"
                                        :alt="event.title"
                                        class="h-20 w-1/2 object-cover transition-transform duration-500 hover:scale-105"
                                    />
                                </div>

                                <Link :href="`/events/${event.id}`" class="text-lg font-semibold hover:text-primary">
                                    {{ event.title }}
                                </Link>
                                <p class="mt-1 line-clamp-2 text-sm text-muted-foreground">{{ event.description }}</p>

                                <div
                                    class="mt-3 flex flex-col gap-1.5 text-sm text-muted-foreground"
                                    :class="index % 2 === 0 ? 'md:items-end' : ''"
                                >
                                    <span class="inline-flex items-center gap-1.5">
                                        <Calendar class="size-3.5 shrink-0" />
                                        {{ event.schedule.event }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <MapPin class="size-3.5 shrink-0" />
                                        {{ event.location }}
                                    </span>
                                </div>

                                <div class="mt-3" :class="index % 2 === 0 ? 'md:text-right' : ''">
                                    <Link :href="`/events/${event.id}`" class="text-sm font-medium text-primary hover:underline">
                                        Register interest →
                                    </Link>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </div>

            <p
                v-else-if="!loading && hasLoadedOnce && !loadError"
                class="py-16 text-center text-muted-foreground"
            >
                No events on the timeline for these filters.
            </p>
        </template>
    </EventVisualShell>
</template>
