<script setup>
import { getIntlLocale, translate } from '@/i18n';
import { Link } from '@inertiajs/vue3';
import ReportShell from '@/Components/UI/ReportShell.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import ReportKpiGrid from '@/Components/UI/ReportKpiGrid.vue';
import { AlertTriangle, ReceiptText } from 'lucide-vue-next';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
    currency: { type: String, default: '€' },
});

const money = (v) => `${props.currency}${Number(v ?? 0).toLocaleString(getIntlLocale(), { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const statusBadge = {
    confirmed: { variant: 'info', label: translate('admin.generated.k_ba233950cbc4') },
    checked_in: { variant: 'success', label: translate('admin.generated.k_ceef4633e6ad') },
    checked_out: { variant: 'neutral', label: translate('admin.generated.k_657b819bd70e') },
};
const fmt = (d) => d ? new Date(d).toLocaleDateString(getIntlLocale(), { day: '2-digit', month: 'short' }) : '—';

const kpis = [
    { label: translate('admin.generated.k_d2e21dc87f23'), value: () => money(props.total), tone: props.total > 0 ? 'error' : 'success', icon: AlertTriangle, detail: translate('admin.generated.k_3998ec811a6f') },
    { label: translate('admin.generated.k_179bf31db787'), value: () => props.rows.length, tone: props.rows.length ? 'warning' : 'success', icon: ReceiptText },
];
</script>

<template>
    <ReportShell :title="$t('admin.generated.k_e5a27d18b6d6')">
        <ReportKpiGrid :items="kpis" />

        <Card :padding="false" class="mt-5">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_11cab5233d4e') }}</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_f97d3a426c51') }}</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_1d8fb31c6b54') }}</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_1574d0f5f4f4') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_1bf361e366ee') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_3c428e14329a') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_b61b4e8cd257') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr v-for="r in rows" :key="r.id" class="hover:bg-neutral-50">
                            <td class="px-5 py-3">
                                <Link :href="route('reservations.show', r.id)" class="text-body-sm text-primary-900 font-medium hover:underline">{{ r.guest }}</Link>
                                <p v-if="r.phone" class="text-tiny text-neutral-400">{{ r.phone }}</p>
                            </td>
                            <td class="px-5 py-3 text-body-sm text-neutral-700">{{ r.room }}</td>
                            <td class="px-5 py-3"><Badge :variant="statusBadge[r.status]?.variant || 'neutral'" size="sm">{{ statusBadge[r.status]?.label || r.status }}</Badge></td>
                            <td class="px-5 py-3 text-body-sm text-neutral-500 whitespace-nowrap">{{ fmt(r.check_in) }} → {{ fmt(r.check_out) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ money(r.gross) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-success-700">{{ money(r.paid) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold text-error-600">{{ money(r.balance) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!rows.length" class="px-6 py-12 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_26879d0779af') }}</div>
        </Card>
    </ReportShell>
</template>
