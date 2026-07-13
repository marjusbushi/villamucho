<script setup>
import { getIntlLocale, translate } from '@/i18n';
import ReportShell from '@/Components/UI/ReportShell.vue';
import Card from '@/Components/UI/Card.vue';
import ReportKpiGrid from '@/Components/UI/ReportKpiGrid.vue';
import ReportBarList from '@/Components/UI/ReportBarList.vue';
import { channelMeta } from '@/channels';
import { computed } from 'vue';
import { Banknote, CalendarCheck, HandCoins, Percent } from 'lucide-vue-next';

const props = defineProps({
    filters: Object,
    rows: { type: Array, default: () => [] },
    totals: Object,
    currency: { type: String, default: '€' },
});

const money = (v) => `${props.currency}${Number(v ?? 0).toLocaleString(getIntlLocale(), { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const commissionRate = () => Number(props.totals?.revenue ?? 0) > 0
    ? `${((Number(props.totals?.commission ?? 0) / Number(props.totals.revenue)) * 100).toLocaleString(getIntlLocale(), { maximumFractionDigits: 1 })}%`
    : '0%';

const kpis = [
    { label: translate('admin.generated.k_8b861e176b4c'), value: () => props.totals?.count ?? 0, tone: 'info', icon: CalendarCheck, detail: translate('admin.generated.k_920ed4640be7', { p0: props.totals?.nights ?? 0 }) },
    { label: translate('admin.generated.k_d3fe93a7afb8'), value: () => money(props.totals?.revenue), tone: 'accent', icon: Banknote },
    { label: translate('admin.generated.k_28eaba77cb31'), value: () => money(props.totals?.commission), tone: 'warning', icon: Percent, detail: () => commissionRate() },
    { label: translate('admin.generated.k_bc7fa976e9e9'), value: () => money(props.totals?.net), tone: 'success', icon: HandCoins },
];

const channelBars = computed(() => props.rows.map((row) => ({
    key: row.channel,
    label: channelMeta(row.channel).label,
    value: Number(row.revenue ?? 0),
    display: money(row.revenue),
    detail: `${row.count ?? 0} rezervime · neto ${money(row.net)}`,
})));
</script>

<template>
    <ReportShell :title="$t('admin.generated.k_05db9dd4e743')" route-name="reports.channels" :filters="filters">
        <ReportKpiGrid :items="kpis" />
        <div class="mt-5 grid gap-4 xl:grid-cols-[minmax(280px,0.65fr)_1.35fr]">
            <ReportBarList :title="$t('admin.generated.k_5a2769c70cfc')" :description="$t('admin.generated.k_1aa86f300620')" :rows="channelBars" />
            <Card :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_0d3e60252296') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_74f1852bb421') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_983a0727d367') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_e3efe194beeb') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_1ff4f40c6b2c') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_20ade973549f') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr v-for="r in rows" :key="r.channel" class="hover:bg-neutral-50">
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center gap-2 text-body-sm text-primary-900">
                                    <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: channelMeta(r.channel).color }" />
                                    {{ channelMeta(r.channel).label }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ r.count }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ r.nights }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-primary-900 font-medium">{{ money(r.revenue) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-error-600">{{ r.commission ? '−' + money(r.commission) : '—' }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-primary-900">{{ money(r.net) }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="rows.length" class="bg-neutral-50 border-t-2 border-neutral-200">
                        <tr>
                            <td class="px-5 py-3 text-body-sm font-semibold text-primary-900">{{ $t('admin.generated.k_11a0efb0b568') }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold">{{ totals.count }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold">{{ totals.nights }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold">{{ money(totals.revenue) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold text-error-600">{{ totals.commission ? '−' + money(totals.commission) : '—' }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold">{{ money(totals.net) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div v-if="!rows.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_1c1f12c82a22') }}</div>
            </Card>
        </div>
    </ReportShell>
</template>
