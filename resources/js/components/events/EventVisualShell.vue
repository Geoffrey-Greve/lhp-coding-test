<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref, computed } from 'vue';
import EventCard from '@/components/events/EventCard.vue';
import EventFilters from '@/components/events/EventFilters.vue';
import { Skeleton } from '@/components/ui/skeleton';
import type { CityOption, VisualEvent, VisualFilters } from '@/types/events';

const props = defineProps<{
    filters: VisualFilters;
    cities: CityOption[];
    title: string;
    subtitle: string;
}>();

const form = reactive<VisualFilters>({ ...props.filters });
const events = ref<VisualEvent[]>([]);
const pageNum = ref(0);
const lastPage = ref<number | null>(null);
const total = ref<number | null>(null);
const loading = ref(false);
const hasLoadedOnce = ref(false);
const sentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

const hasMore = computed(() => lastPage.value === null || pageNum.value < lastPage.value);

async function loadMore() {
    if (loading.value || !hasMore.value) {
return;
}

    loading.value = true;

    const params = new URLSearchParams({ page: String(pageNum.value + 1) });

    if (form.from) {
params.set('from', form.from);
}

    if (form.to) {
params.set('to', form.to);
}

    if (form.city) {
params.set('city', form.city);
}

    try {
        const response = await fetch(`/events/visuals/data?${params.toString()}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const payload = await response.json();
        events.value.push(...payload.data);
        pageNum.value = payload.current_page;
        lastPage.value = payload.last_page;
        total.value = payload.total;
        hasLoadedOnce.value = true;
    } finally {
        loading.value = false;
    }
}

function applyFilters(filters: VisualFilters) {
    form.from = filters.from;
    form.to = filters.to;
    form.city = filters.city;
    events.value = [];
    pageNum.value = 0;
    lastPage.value = null;
    total.value = null;
    hasLoadedOnce.value = false;
    loadMore();
}

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting) {
loadMore();
}
        },
        { rootMargin: '400px' },
    );

    if (sentinel.value) {
observer.observe(sentinel.value);
}

    loadMore();
});

onBeforeUnmount(() => observer?.disconnect());

defineExpose({ form, events, applyFilters });
</script>

<template>
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="space-y-1">
            <h1 class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
            <p class="text-sm text-muted-foreground">
                {{ subtitle }}
                <span v-if="total !== null"> · {{ total.toLocaleString() }} events</span>
            </p>
        </div>

        <EventFilters :filters="form" :cities="cities" @apply="applyFilters" />

        <slot name="content" :events="events" :loading="loading" :has-loaded-once="hasLoadedOnce">
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                <EventCard v-for="(event, index) in events" :key="event.id" :event="event" :index="index" />
            </div>
        </slot>

        <div v-if="loading" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            <Skeleton v-for="n in 3" :key="n" class="h-80 rounded-xl" />
        </div>

        <p v-if="!loading && hasLoadedOnce && events.length === 0" class="py-16 text-center text-muted-foreground">
            No events match your filters. Try widening the date range or choosing another city.
        </p>

        <div ref="sentinel" class="h-1" />
    </div>
</template>
