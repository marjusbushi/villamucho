<script setup>
import { getIntlLocale, translate } from '@/i18n';
import ReportShell from '@/Components/UI/ReportShell.vue';
import Card from '@/Components/UI/Card.vue';
import ReportKpiGrid from '@/Components/UI/ReportKpiGrid.vue';
import ReportBarList from '@/Components/UI/ReportBarList.vue';
import { computed } from 'vue';
import { Banknote, BedDouble, ChartNoAxesCombined, Percent } from 'lucide-vue-next';

const props = defineProps({
    filters: Object,
    rows: { type: Array, default: () => [] },
    totals: Object,
    currency: { type: String, default: '€' },
});

const money = (v) => `${props.currency}${Number(v ?? 0).toLocaleString(getIntlLocale(), { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const pct = (v) => `${Number(v ?? 0).toLocaleString(getIntlLocale(), { minimumFractionDigits: 1, maximumFractionDigits: 1 })}%`;

const kpis = [
    { label: translate('admin.generated.k_4b13f7c047cd'), value: () => money(props.totals?.adr), tone: 'accent', icon: ChartNoAxesCombined, detail: translate('admin.generated.k_a025debf3223') },
    { label: translate('admin.generated.k_642883f19383'), value: () => money(props.totals?.revpar), tone: 'success', icon: Banknote, detail: translate('admin.generated.k_1d15b59494e5') },
    { label: translate('admin.generated.k_9e513e428aef'), value: () => pct(props.totals?.occupancy), tone: 'info', icon: Percent, detail: translate('admin.generated.k_ed1aeaa347ea', { p0: props.totals?.nights ?? 0 }) },
    { label: translate('admin.generated.k_9f75b07d948d'), value: () => money(props.totals?.revenue), tone: 'neutral', icon: BedDouble, detail: translate('admin.generated.k_b5c09bc8cfd3') },
];

const occupancyBars = computed(() => props.rows.map((row) => ({
    key: row.type,
    label: row.type,
    value: Number(row.occupancy ?? 0),
    display: pct(row.occupancy),
    detail: translate('admin.generated.k_6f8c764a3e8e', { p0: row.nights ?? 0, p1: money(row.revenue) }),
    barClass: Number(row.occupancy ?? 0) >= 80 ? 'bg-success-500' : Number(row.occupancy ?? 0) >= 50 ? 'bg-accent-500' : 'bg-warning-500',
})));
</script>

<template>
    <ReportShell :title="$t('admin.generated.k_8c78bfca940c')" route-name="reports.performance" :filters="filters">
        <ReportKpiGrid :items="kpis" />

        <div class="mt-5 grid gap-4 xl:grid-cols-[minmax(280px,0.65fr)_1.35fr]">
            <ReportBarList :title="$t('admin.generated.k_e924ba29aea4')" :description="$t('admin.generated.k_7c5a886f3a18')" :rows="occupancyBars" />
            <Card :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_e63b890ba7f9') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_e91df5449265') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_146fe8025e21') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_d23f18a3f1d3') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_36a389a245eb') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_b912109ef9e1') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_6ecffc614f96') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr v-for="r in rows" :key="r.type" class="hover:bg-neutral-50">
                            <td class="px-5 py-3 text-body-sm text-primary-900 font-medium">{{ r.type }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ r.rooms_count }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ r.nights }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-primary-900 font-medium">{{ money(r.revenue) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ money(r.adr) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ pct(r.occupancy) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-primary-900">{{ money(r.revpar) }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="rows.length" class="bg-neutral-50 border-t-2 border-neutral-200">
                        <tr>
                            <td class="px-5 py-3 text-body-sm font-semibold text-primary-900">{{ $t('admin.generated.k_53b23f0193bf') }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold">{{ totals.rooms_count }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold">{{ totals.nights }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold">{{ money(totals.revenue) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold">{{ money(totals.adr) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold">{{ pct(totals.occupancy) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold">{{ money(totals.revpar) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div v-if="!rows.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_4f6f3cf01a58') }}</div>
            </Card>
        </div>
    </ReportShell>
</template>
