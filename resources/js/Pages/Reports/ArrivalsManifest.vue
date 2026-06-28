<script setup>
import { Link } from '@inertiajs/vue3';
import ReportShell from '@/Components/UI/ReportShell.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import { channelMeta } from '@/channels';

const props = defineProps({
    filters: Object,
    rows: { type: Array, default: () => [] },
    totals: Object,
    currency: { type: String, default: '€' },
});

const money = (v) => `${props.currency}${Number(v ?? 0).toLocaleString('sq-AL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const statusBadge = {
    pending: { variant: 'warning', label: 'Në pritje' },
    confirmed: { variant: 'info', label: 'Konfirmuar' },
    checked_in: { variant: 'success', label: 'Brenda' },
};

const fmt = (d) => d ? new Date(d).toLocaleDateString('sq-AL', { weekday: 'short', day: '2-digit', month: 'short' }) : '—';
</script>

<template>
    <ReportShell title="Manifesti i Mbërritjeve" route-name="reports.arrivalsManifest" :filters="filters">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            <Card>
                <div class="text-center">
                    <p class="text-h3 text-primary-900">{{ totals?.count ?? 0 }}</p>
                    <p class="text-tiny text-neutral-500 uppercase tracking-wider mt-1">Mbërritje</p>
                </div>
            </Card>
            <Card>
                <div class="text-center">
                    <p class="text-h3 text-primary-900">{{ totals?.nights ?? 0 }}</p>
                    <p class="text-tiny text-neutral-500 uppercase tracking-wider mt-1">Net gjithsej</p>
                </div>
            </Card>
            <Card>
                <div class="text-center">
                    <p class="text-h3 text-primary-900">{{ totals?.pax ?? 0 }}</p>
                    <p class="text-tiny text-neutral-500 uppercase tracking-wider mt-1">Persona</p>
                </div>
            </Card>
            <Card>
                <div class="text-center">
                    <p class="text-h3 text-primary-900">{{ money(totals?.revenue) }}</p>
                    <p class="text-tiny text-neutral-500 uppercase tracking-wider mt-1">Të ardhura pritura</p>
                </div>
            </Card>
        </div>

        <Card :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">Mbërritja</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">Mysafiri</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">Dhoma</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">Statusi</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">Net</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">Persona</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">Burimi</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">Mbetet</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">Shënime</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr v-for="r in rows" :key="r.id" class="hover:bg-neutral-50">
                            <td class="px-5 py-3 text-body-sm text-neutral-700 whitespace-nowrap">{{ fmt(r.check_in) }}</td>
                            <td class="px-5 py-3">
                                <Link :href="route('reservations.show', r.id)" class="text-body-sm text-primary-900 font-medium hover:underline">{{ r.guest }}</Link>
                                <p v-if="r.phone" class="text-tiny text-neutral-400">{{ r.phone }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <p class="text-body-sm text-neutral-700">{{ r.room || '—' }}</p>
                                <p v-if="r.room_type" class="text-tiny text-neutral-400">{{ r.room_type }}</p>
                            </td>
                            <td class="px-5 py-3"><Badge :variant="statusBadge[r.status]?.variant || 'neutral'" size="sm">{{ statusBadge[r.status]?.label || r.status }}</Badge></td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ r.nights }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700 whitespace-nowrap">
                                {{ r.pax }}
                                <span class="text-tiny text-neutral-400">({{ r.adults }}+{{ r.children }})</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center gap-1.5 text-body-sm text-neutral-700">
                                    <span class="inline-block h-2 w-2 rounded-full" :style="{ backgroundColor: channelMeta(r.channel).color }"></span>
                                    {{ channelMeta(r.channel).label }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold" :class="r.balance > 0.009 ? 'text-error-600' : 'text-success-700'">{{ money(r.balance) }}</td>
                            <td class="px-5 py-3 text-body-sm text-neutral-500 max-w-[16rem] truncate" :title="r.notes || ''">{{ r.notes || '—' }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="rows.length" class="bg-neutral-50 border-t-2 border-neutral-200">
                        <tr class="font-semibold">
                            <td class="px-5 py-3 text-body-sm text-neutral-700" colspan="4">Gjithsej ({{ totals?.count ?? 0 }})</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ totals?.nights ?? 0 }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ totals?.pax ?? 0 }}</td>
                            <td class="px-5 py-3"></td>
                            <td class="px-5 py-3 text-right text-body-sm text-error-600">{{ money(totals?.balance) }}</td>
                            <td class="px-5 py-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div v-if="!rows.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">Asnjë mbërritje për këtë periudhë.</div>
        </Card>
    </ReportShell>
</template>
