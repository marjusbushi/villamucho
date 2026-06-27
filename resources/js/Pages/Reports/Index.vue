<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
    filters: Object,
    summary: Object,
    byStatus: Array,
    currency: { type: String, default: '€' },
});

const from = ref(props.filters.from);
const to = ref(props.filters.to);

const statusBadge = {
    pending: { variant: 'warning', label: 'Ne pritje' },
    confirmed: { variant: 'info', label: 'Konfirmuar' },
    checked_in: { variant: 'success', label: 'Brenda' },
    checked_out: { variant: 'neutral', label: 'Larguar' },
};

function apply() {
    router.get('/pms/reports', { from: from.value, to: to.value }, { preserveState: true });
}

function money(v) {
    return `${props.currency}${Number(v ?? 0).toFixed(2)}`;
}

const cards = [
    { label: 'Rezervime', value: () => props.summary.reservation_count },
    { label: 'Nete te shitura', value: () => props.summary.nights_sold },
    { label: 'Te ardhura dhomash', value: () => money(props.summary.room_revenue) },
    { label: 'Porosi POS', value: () => props.summary.pos_count },
    { label: 'Te ardhura POS', value: () => money(props.summary.pos_revenue) },
    { label: 'Total', value: () => money(props.summary.total_revenue), accent: true },
];
</script>

<template>
    <AppLayout>
        <PageHeader
            title="Raporte"
            :breadcrumbs="[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Raporte' }]"
        />

        <!-- Date range -->
        <div class="mt-6 flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-label text-neutral-600 mb-1.5">Nga</label>
                <input type="date" v-model="from" class="rounded-lg border border-neutral-200 px-3 py-2 text-body-sm" />
            </div>
            <div>
                <label class="block text-label text-neutral-600 mb-1.5">Deri</label>
                <input type="date" v-model="to" class="rounded-lg border border-neutral-200 px-3 py-2 text-body-sm" />
            </div>
            <Button variant="primary" @click="apply">Apliko</Button>
        </div>

        <!-- Summary cards -->
        <div class="mt-6 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
            <Card v-for="c in cards" :key="c.label">
                <div class="text-center">
                    <p :class="['text-h3', c.accent ? 'text-accent-600' : 'text-primary-900']">{{ c.value() }}</p>
                    <p class="text-tiny text-neutral-500 uppercase tracking-wider mt-1">{{ c.label }}</p>
                </div>
            </Card>
        </div>

        <!-- Breakdown by status -->
        <div class="mt-6">
            <Card :padding="false">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h3 class="text-label text-neutral-600 uppercase tracking-wider">Sipas statusit (hyrje ne periudhe)</h3>
                </div>
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">Statusi</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">Rezervime</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">Te ardhura</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr v-for="row in byStatus" :key="row.status" class="hover:bg-neutral-50">
                            <td class="px-5 py-3">
                                <Badge :variant="statusBadge[row.status]?.variant || 'neutral'">{{ statusBadge[row.status]?.label || row.status }}</Badge>
                            </td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ row.count }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-primary-900">{{ money(row.revenue) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!byStatus.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">
                    Asnje rezervim ne kete periudhe.
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
