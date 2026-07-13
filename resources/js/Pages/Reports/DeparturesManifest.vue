<script setup>
import { getIntlLocale, translate } from '@/i18n';
import { Link } from '@inertiajs/vue3';
import ReportShell from '@/Components/UI/ReportShell.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import ReportKpiGrid from '@/Components/UI/ReportKpiGrid.vue';
import { AlertTriangle, CalendarCheck, ReceiptText, Utensils } from 'lucide-vue-next';

const props = defineProps({
    filters: Object,
    rows: { type: Array, default: () => [] },
    totals: Object,
    currency: { type: String, default: '€' },
});

const money = (v) => `${props.currency}${Number(v ?? 0).toLocaleString(getIntlLocale(), { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const statusBadge = {
    checked_in: { variant: 'success', label: translate('admin.generated.k_bef93f33c4cb') },
    checked_out: { variant: 'neutral', label: translate('admin.generated.k_3d8fa9469e00') },
};

const fmt = (d) => d ? new Date(d).toLocaleDateString(getIntlLocale(), { day: '2-digit', month: 'short' }) : '—';

const kpis = [
    { label: translate('admin.generated.k_0302e94b3a7e'), value: () => props.totals?.count ?? 0, tone: 'accent', icon: CalendarCheck, detail: translate('admin.generated.k_6c8d11e88e8b') },
    { label: translate('admin.generated.k_cbc2964b44b6'), value: () => money(props.totals?.outstanding), tone: () => Number(props.totals?.outstanding ?? 0) > 0 ? 'error' : 'success', icon: ReceiptText, detail: translate('admin.generated.k_e45328692cc6') },
    { label: translate('admin.generated.k_5d6587a27f24'), value: () => props.rows.filter((row) => Number(row.balance ?? 0) > 0).length, tone: 'warning', icon: AlertTriangle },
    { label: translate('admin.generated.k_5ed9898d264c'), value: () => props.rows.reduce((sum, row) => sum + Number(row.open_pos_count ?? 0), 0), tone: 'neutral', icon: Utensils },
];
</script>

<template>
    <ReportShell :title="$t('admin.generated.k_a39120663cac')" route-name="reports.departuresManifest" :filters="filters">
        <ReportKpiGrid :items="kpis" />

        <Card :padding="false" class="mt-5">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_63ad18720227') }}</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_b85fc463451e') }}</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_599384144d2d') }}</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_7bdc94e1b496') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_a1ef1321a505') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_f929042ad02e') }}</th>
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
                            <td class="px-5 py-3 text-body-sm text-neutral-700" colspan="5">{{ totals?.count ?? rows.length }} {{ $t('admin.generated.k_cbfe0ce35ea9') }}</td>
                            <td class="px-5 py-3 text-right text-body-sm" :class="(totals?.outstanding ?? 0) > 0 ? 'text-error-600' : 'text-success-700'">{{ money(totals?.outstanding) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div v-if="!rows.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_9124bf02c560') }}</div>
        </Card>
    </ReportShell>
</template>
