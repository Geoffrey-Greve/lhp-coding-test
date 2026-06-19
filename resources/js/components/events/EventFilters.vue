<script setup lang="ts">
import { reactive, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { CityOption, VisualFilters } from '@/types/events';

const props = defineProps<{
    filters: VisualFilters;
    cities: CityOption[];
}>();

const emit = defineEmits<{
    apply: [filters: VisualFilters];
}>();

const form = reactive<VisualFilters>({
    from: props.filters.from,
    to: props.filters.to,
    city: props.filters.city,
});

watch(
    () => props.filters,
    (value) => {
        form.from = value.from;
        form.to = value.to;
        form.city = value.city;
    },
    { deep: true },
);

function submit() {
    emit('apply', { ...form });
}
</script>

<template>
    <form class="flex flex-wrap items-end gap-3 rounded-xl border bg-card/60 p-4 backdrop-blur-sm" @submit.prevent="submit">
        <div class="flex min-w-[10rem] flex-col gap-1.5">
            <Label for="from" class="text-xs text-muted-foreground">From</Label>
            <Input id="from" v-model="form.from" type="date" class="h-9" />
        </div>
        <div class="flex min-w-[10rem] flex-col gap-1.5">
            <Label for="to" class="text-xs text-muted-foreground">To</Label>
            <Input id="to" v-model="form.to" type="date" class="h-9" />
        </div>
        <div class="flex min-w-[12rem] flex-1 flex-col gap-1.5">
            <Label for="city" class="text-xs text-muted-foreground">Location</Label>
            <select
                id="city"
                v-model="form.city"
                class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            >
                <option value="">All cities</option>
                <option v-for="city in cities" :key="city.slug" :value="city.slug">{{ city.name }}</option>
            </select>
        </div>
        <Button type="submit" class="transition-transform hover:scale-[1.02] active:scale-[0.98]">Apply filters</Button>
    </form>
</template>
