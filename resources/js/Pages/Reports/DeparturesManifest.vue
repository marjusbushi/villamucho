<script setup>
import { Link } from '@inertiajs/vue3';
import ReportShell from '@/Components/UI/ReportShell.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
    filters: Object,
    rows: { type: Array, default: () => [] },
    totals: Object,
    currency: { type: String, default: '€' },
});

const money = (v) => `${props.currency}${Number(v ?? 0).toLocaleString('sq-AL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const statusBadge = {
    checked_in: { variant: 'success', label: 'Brenda' },
    checked_out: { variant: 'neutral', label: 'Larguar' },
};

const fmt = (d) => d ? new Date(d).toLocaleDateString('sq-AL', { day: '2-digit', month: 'short' }) : '—';
</script>

<template>
    <ReportShell title="Manifesti i Nisjeve" route-name="reports.departuresManifest" :filters="filters">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            <Card>
                <div class="text-center">
                    <p class="text-h3 text-primary-900">{{ totals?.count ?? 0 }}</p>
                    <p class="text-tiny text-neutral-500 uppercase tracking-wider mt-1">Nisje</p>
                </div>
            </Card>
            <Card>
                <div class="text-center">
                    <p class="text-h3" :class="(totals?.outstanding ?? 0) > 0 ? 'text-error-600' : 'text-primary-900'">{{ money(totals?.outstanding) }}</p>
                    <p class="text-tiny text-neutral-500 uppercase tracking-wider mt-1">Bilanc i Hapur</p>
                </div>
            </Card>
        </div>

        <Card :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">Nisja</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">Mysafiri</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">Dhoma</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">Statusi</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">Porosi POS të Hapura</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">Mbetet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr v-for="r in rows" :key="r.id" class="hover:bg-neutral-50" :class="r.balance > 0 ? 'bg-error-50/40' : ''">
                            <td class="px-5 py-3 text-body-sm text-neutral-700 whitespace-nowrap">{{ fmt(r.check_out) }}</td>
                            <td class="px-5 py-3">
                                <Link :href="route('reservations.show', r.id)" class="text-body-sm text-primary-900 font-medium hover:underline">{{ r.guest }}</Link>
                                <p v-if="r.phone" class="text-tiny text-neutral-400">{{ r.phone }}</p>
                            </td>
                            <td class="px-5 py-3 text-body-sm text-neutral-700">{{ r.room || '—' }}</td>
                            <td class="px-5 py-3"><Badge :variant="statusBadge[r.status]?.variant || 'neutral'" size="sm">{{ statusBadge[r.status]?.label || r.status }}</Badge></td>
                            <td class="px-5 py-3 text-right">
                                <Badge v-if="r.open_pos_count > 0" variant="warning" size="sm">{{ r.open_pos_count }}</Badge>
                                <span v-else class="text-body-sm text-neutral-400">—</span>
                            </td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold" :class="r.balance > 0 ? 'text-error-600' : 'text-success-700'">{{ money(r.balance) }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="rows.length" class="bg-neutral-50 border-t-2 border-neutral-200">
                        <tr class="font-semibold">
                            <td class="px-5 py-3 text-body-sm text-neutral-700" colspan="5">{{ totals?.count ?? rows.length }} nisje gjithsej</td>
                            <td class="px-5 py-3 text-right text-body-sm" :class="(totals?.outstanding ?? 0) > 0 ? 'text-error-600' : 'text-success-700'">{{ money(totals?.outstanding) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div v-if="!rows.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">Asnjë të dhënë.</div>
        </Card>
    </ReportShell>
</template>
