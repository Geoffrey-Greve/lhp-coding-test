<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Calendar, ChevronLeft, ChevronRight, MapPin, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AttendeePreview, VisualEvent } from '@/types/events';

const props = defineProps<{
    event: VisualEvent;
    attendees: AttendeePreview[];
}>();

const activeImage = ref(0);
const images = computed(() => props.event.images);

const form = useForm({
    name: '',
    email: '',
});

function submit() {
    form.post(`/events/${props.event.id}/attendees`, {
        preserveScroll: true,
        onSuccess: () => form.reset('email'),
    });
}

function prevImage() {
    if (images.value.length === 0) {
return;
}

    activeImage.value = (activeImage.value - 1 + images.value.length) % images.value.length;
}

function nextImage() {
    if (images.value.length === 0) {
return;
}

    activeImage.value = (activeImage.value + 1) % images.value.length;
}

const statusVariant = computed(() => {
    switch (props.event.status) {
        case 'published':
            return 'default';
        case 'cancelled':
            return 'destructive';
        case 'sold_out':
            return 'secondary';
        default:
            return 'outline';
    }
});
</script>

<template>
    <Head :title="event.title" />

    <div class="mx-auto flex max-w-5xl flex-col gap-6 p-4 md:p-6">
        <Link href="/events-visual-1" class="inline-flex items-center gap-1 text-sm text-primary hover:underline">
            <ChevronLeft class="size-4" />
            Back to events
        </Link>

        <div class="grid gap-6 lg:grid-cols-5">
            <div class="space-y-4 lg:col-span-3">
                <div class="relative overflow-hidden rounded-2xl bg-muted animate-in fade-in duration-500">
                    <img
                        :src="images[activeImage]?.url ?? '/images/events/placeholder-1.svg'"
                        :alt="event.title"
                        class="aspect-[16/10] w-full object-cover"
                    />
                    <div v-if="images.length > 1" class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/60 to-transparent p-4">
                        <Button size="icon" variant="secondary" class="size-8" @click="prevImage">
                            <ChevronLeft class="size-4" />
                        </Button>
                        <div class="flex gap-2">
                            <button
                                v-for="(_, i) in images"
                                :key="i"
                                type="button"
                                class="h-2 w-2 rounded-full transition-all"
                                :class="i === activeImage ? 'scale-125 bg-white' : 'bg-white/50'"
                                @click="activeImage = i"
                            />
                        </div>
                        <Button size="icon" variant="secondary" class="size-8" @click="nextImage">
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Badge :variant="statusVariant" class="capitalize">{{ event.status }}</Badge>
                    <Badge variant="outline" class="capitalize">{{ event.type }}</Badge>
                </div>

                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ event.title }}</h1>
                    <p class="mt-3 text-muted-foreground">{{ event.description }}</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Calendar class="size-4" />
                                When
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm text-muted-foreground">{{ event.schedule.event }}</CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="flex items-center gap-2 text-base">
                                <MapPin class="size-4" />
                                Where
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm text-muted-foreground">{{ event.location }}</CardContent>
                    </Card>
                </div>
            </div>

            <div class="space-y-4 lg:col-span-2">
                <Card class="animate-in fade-in slide-in-from-right-4 duration-500">
                    <CardHeader>
                        <CardTitle>Register your interest</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form class="space-y-4" @submit.prevent="submit">
                            <div class="space-y-1.5">
                                <Label for="name">Name</Label>
                                <Input id="name" v-model="form.name" required placeholder="Jane Doe" />
                                <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="email">Email</Label>
                                <Input id="email" v-model="form.email" type="email" required placeholder="jane@example.com" />
                                <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                            </div>
                            <Button type="submit" class="w-full" :disabled="form.processing">
                                {{ form.processing ? 'Joining…' : 'Join attendee list' }}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Users class="size-4" />
                            Attendees
                            <Badge variant="secondary">{{ event.attendee_count ?? attendees.length }}</Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul v-if="attendees.length > 0" class="space-y-2 text-sm">
                            <li
                                v-for="attendee in attendees"
                                :key="attendee.id"
                                class="flex items-center justify-between rounded-md border px-3 py-2"
                            >
                                <span class="font-medium">{{ attendee.name }}</span>
                                <span class="text-xs text-muted-foreground">{{ attendee.email }}</span>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-muted-foreground">Be the first to register!</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
