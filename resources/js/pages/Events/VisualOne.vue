<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import EventVisualShell from '@/components/events/EventVisualShell.vue';
import type { CityOption, VisualFilters } from '@/types/events';

defineProps<{
    filters: VisualFilters;
    cities: CityOption[];
}>();
</script>

<template>
    <Head title="Events Visual 1" />

    <EventVisualShell
        :filters="filters"
        :cities="cities"
        title="Event Visuals — Gallery"
        subtitle="Browse upcoming events in a card grid with image previews"
    />
</template>
