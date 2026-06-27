<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import { Head, usePage, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    arrivals: Array,
    departures: Array,
    currency: { type: String, default: '€' },
});

const user = usePage().props.auth.user;

const statusBadge = {
    pending: { variant: 'warning', label: 'Ne pritje' },
    confirmed: { variant: 'info', label: 'Konfirmuar' },
    checked_in: { variant: 'success', label: 'Brenda' },
    checked_out: { variant: 'neutral', label: 'Larguar' },
    cancelled: { variant: 'error', label: 'Anulluar' },
};

const cards = [
    { key: 'occupancy', label: 'Zenia sot', value: () => `${props.stats.occupancy}%`, sub: () => `${props.stats.occupied}/${props.stats.total_rooms} dhoma` },
    { key: 'arrivals', label: 'Hyrje sot', value: () => props.stats.arrivals },
    { key: 'departures', label: 'Dalje sot', value: () => props.stats.departures },
    { key: 'to_clean', label: 'Per pastrim', value: () => props.stats.to_clean },
    { key: 'open_pos', label: 'POS te hapura', value: () => props.stats.open_pos },
    { key: 'revenue', label: 'Te ardhura POS sot', value: () => `${props.currency}${Number(props.stats.pos_revenue_today).toFixed(2)}`, accent: true },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <PageHeader title="Dashboard" />

        <!-- Welcome -->
        <div class="mt-6">
            <Card>
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-lg bg-accent-100 flex items-center justify-center">
                        <span class="text-h4 text-accent-700">{{ user.name.charAt(0) }}</span>
                    </div>
                    <div>
                        <h3 class="text-h4 text-primary-900">Miresevini, {{ user.name }}!</h3>
                        <div class="flex items-center gap-2 mt-1">
                            <Badge variant="accent">{{ user.role }}</Badge>
                            <span class="text-body-sm text-neutral-500">{{ user.email }}</span>
                        </div>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Stat cards -->
        <div class="mt-6 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
            <Card v-for="c in cards" :key="c.key">
                <div class="text-center">
                    <p :class="['text-h3', c.accent ? 'text-accent-600' : 'text-primary-900']">{{ c.value() }}</p>
                    <p class="text-tiny text-neutral-500 uppercase tracking-wider mt-1">{{ c.label }}</p>
                    <p v-if="c.sub" class="text-tiny text-neutral-400 mt-0.5">{{ c.sub() }}</p>
                </div>
            </Card>
        </div>

        <!-- Today's arrivals & departures -->
        <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card :padding="false">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h3 class="text-label text-neutral-600 uppercase tracking-wider">Hyrjet e sotme</h3>
                </div>
                <ul class="divide-y divide-neutral-100">
                    <li v-for="r in arrivals" :key="r.id" class="px-5 py-3 flex items-center justify-between hover:bg-neutral-50">
                        <Link :href="route('reservations.show', r.id)" class="text-body-sm text-primary-900 font-medium hover:underline">
                            {{ r.guest || 'Mysafir' }}
                        </Link>
                        <div class="flex items-center gap-3">
                            <span class="text-body-sm text-neutral-500">Dhoma {{ r.room }}</span>
                            <Badge :variant="statusBadge[r.status]?.variant" dot>{{ statusBadge[r.status]?.label }}</Badge>
                        </div>
                    </li>
                </ul>
                <div v-if="!arrivals.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">Asnje hyrje sot.</div>
            </Card>

            <Card :padding="false">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h3 class="text-label text-neutral-600 uppercase tracking-wider">Daljet e sotme</h3>
                </div>
                <ul class="divide-y divide-neutral-100">
                    <li v-for="r in departures" :key="r.id" class="px-5 py-3 flex items-center justify-between hover:bg-neutral-50">
                        <Link :href="route('reservations.show', r.id)" class="text-body-sm text-primary-900 font-medium hover:underline">
                            {{ r.guest || 'Mysafir' }}
                        </Link>
                        <div class="flex items-center gap-3">
                            <span class="text-body-sm text-neutral-500">Dhoma {{ r.room }}</span>
                            <Badge :variant="statusBadge[r.status]?.variant" dot>{{ statusBadge[r.status]?.label }}</Badge>
                        </div>
                    </li>
                </ul>
                <div v-if="!departures.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">Asnje dalje sot.</div>
            </Card>
        </div>
    </AppLayout>
</template>
