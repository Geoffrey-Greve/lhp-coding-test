<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Calendar, MapPin, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import type { VisualEvent } from '@/types/events';

const props = defineProps<{
    event: VisualEvent;
    index?: number;
}>();

const activeImage = ref(0);

const images = computed(() => (props.event.images.length > 0 ? props.event.images : [{ url: '/images/events/placeholder-1.svg', path: '' }]));

function selectImage(index: number) {
    activeImage.value = index;
}

function nextImage() {
    activeImage.value = (activeImage.value + 1) % images.value.length;
}
</script>

<template>
    <Card
        class="group overflow-hidden border-border/70 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
        :class="index !== undefined ? 'animate-in fade-in slide-in-from-bottom-4 fill-mode-both' : ''"
        :style="index !== undefined ? { animationDelay: `${Math.min(index, 12) * 50}ms`, animationDuration: '500ms' } : undefined"
    >
        <div class="relative aspect-[16/10] overflow-hidden bg-muted">
            <img
                :src="images[activeImage]?.url"
                :alt="event.title"
                class="h-full w-full object-cover transition-opacity duration-500"
            />
            <div
                v-if="images.length > 1"
                class="absolute inset-x-0 bottom-0 flex justify-center gap-1.5 bg-gradient-to-t from-black/50 to-transparent p-3 opacity-0 transition-opacity group-hover:opacity-100"
            >
                <button
                    v-for="(_, i) in images"
                    :key="i"
                    type="button"
                    class="h-2 w-2 rounded-full transition-all"
                    :class="i === activeImage ? 'scale-125 bg-white' : 'bg-white/50 hover:bg-white/80'"
                    @click.prevent="selectImage(i)"
                />
            </div>
            <button
                v-if="images.length > 1"
                type="button"
                class="absolute right-2 top-2 rounded-full bg-black/40 px-2 py-0.5 text-xs text-white opacity-0 backdrop-blur transition-opacity group-hover:opacity-100"
                @click.prevent="nextImage"
            >
                {{ activeImage + 1 }}/{{ images.length }}
            </button>
            <Badge class="absolute left-3 top-3 capitalize" variant="secondary">{{ event.type }}</Badge>
        </div>

        <CardHeader class="space-y-2 pb-2">
            <Link :href="`/events/${event.id}`" class="line-clamp-2 text-lg font-semibold leading-snug hover:text-primary">
                {{ event.title }}
            </Link>
            <p class="line-clamp-2 text-sm text-muted-foreground">{{ event.description }}</p>
        </CardHeader>

        <CardContent class="space-y-2 pb-3 text-sm">
            <p class="flex items-start gap-2 text-muted-foreground">
                <Calendar class="mt-0.5 size-4 shrink-0" />
                <span>{{ event.schedule.event }}</span>
            </p>
            <p class="flex items-start gap-2 text-muted-foreground">
                <MapPin class="mt-0.5 size-4 shrink-0" />
                <span>{{ event.location }}</span>
            </p>
        </CardContent>

        <CardFooter class="flex items-center justify-between border-t pt-3 text-xs text-muted-foreground">
            <span v-if="event.attendee_count !== undefined" class="inline-flex items-center gap-1">
                <Users class="size-3.5" />
                {{ event.attendee_count }} attending
            </span>
            <Link :href="`/events/${event.id}`" class="font-medium text-primary hover:underline">Details →</Link>
        </CardFooter>
    </Card>
</template>
