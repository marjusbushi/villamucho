<script setup>
import { Link } from '@inertiajs/vue3';
import ReportShell from '@/Components/UI/ReportShell.vue';
import Card from '@/Components/UI/Card.vue';

const props = defineProps({
    filters: Object,
    rows: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
    currency: { type: String, default: '€' },
});

const money = (v) => `${props.currency}${Number(v ?? 0).toLocaleString('sq-AL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const fmt = (d) => d ? new Date(d).toLocaleDateString('sq-AL', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
</script>

<template>
    <ReportShell title="Zbritje të Dhëna" route-name="reports.discounts" :filters="filters">
        <Card class="mb-4">
            <div class="flex items-center justify-between">
                <p class="text-body-sm text-neutral-600">Zbritje totale të dhëna në periudhë</p>
                <p class="text-h2 text-warning-600">{{ money(total) }}</p>
            </div>
            <p class="text-tiny text-neutral-500 mt-1">{{ rows.length }} zbritje</p>
        </Card>

        <Card :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">Data</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">Mysafiri</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">Dhoma</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">Përshkrimi</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">Shuma</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr v-for="r in rows" :key="r.id" class="hover:bg-neutral-50">
                            <td class="px-5 py-3 text-body-sm text-neutral-500 whitespace-nowrap">{{ fmt(r.date) }}</td>
                            <td class="px-5 py-3 text-body-sm">
                                <Link v-if="r.reservation_id" :href="route('reservations.show', r.reservation_id)" class="text-primary-900 font-medium hover:underline">{{ r.guest }}</Link>
                                <span v-else class="text-primary-900">{{ r.guest }}</span>
                            </td>
                            <td class="px-5 py-3 text-body-sm text-neutral-700">{{ r.room || '—' }}</td>
                            <td class="px-5 py-3 text-body-sm text-neutral-700">{{ r.description || '—' }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-medium text-warning-600">−{{ money(r.amount) }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="rows.length" class="bg-neutral-50 border-t-2 border-neutral-200">
                        <tr>
                            <td class="px-5 py-3 text-body-sm font-semibold text-primary-900" colspan="4">Totali</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold text-warning-600">−{{ money(total) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div v-if="!rows.length" class="px-6 py-12 text-center text-body-sm text-neutral-500">Asnjë zbritje në këtë periudhë.</div>
        </Card>
    </ReportShell>
</template>
