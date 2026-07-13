<script setup>
import { getIntlLocale, translate } from '@/i18n';
import ReportShell from '@/Components/UI/ReportShell.vue';
import Card from '@/Components/UI/Card.vue';
import ReportKpiGrid from '@/Components/UI/ReportKpiGrid.vue';
import { channelMeta } from '@/channels';
import { CalendarClock, CalendarDays, Radio, ReceiptText } from 'lucide-vue-next';

const props = defineProps({
    filters: Object,
    rows: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    currency: { type: String, default: '€' },
});

const num = (v, d = 1) =>
    Number(v ?? 0).toLocaleString(getIntlLocale(), { minimumFractionDigits: d, maximumFractionDigits: d });

const kpis = [
    { label: translate('admin.generated.k_ac37d93af823'), value: () => Number(props.summary.count ?? 0).toLocaleString(getIntlLocale()), tone: 'accent', icon: ReceiptText },
    { label: translate('admin.generated.k_11a7c4b7e29a'), value: () => translate('admin.generated.k_33a75341c2fc', { p0: num(props.summary.avg_lead) }), tone: 'info', icon: CalendarClock, detail: translate('admin.generated.k_a9d37ddb00c3') },
    { label: translate('admin.generated.k_bdb3594cf5ce'), value: () => translate('admin.generated.k_a29cd28bca85', { p0: num(props.summary.avg_los) }), tone: 'success', icon: CalendarDays },
    { label: translate('admin.generated.k_b9a24a6bf88d'), value: () => props.rows.length, tone: 'neutral', icon: Radio },
];
</script>

<template>
    <ReportShell :title="$t('admin.generated.k_e2fe2271837d')" route-name="reports.bookingBehavior" :filters="filters">
        <ReportKpiGrid :items="kpis" />

        <!-- Per-channel table -->
        <Card class="mt-6 overflow-hidden !p-0">
            <table v-if="rows.length" class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_a3c21020059c') }}</th>
                        <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_3258357672d4') }}</th>
                        <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_649262b23015') }}</th>
                        <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_f2f63bf0304c') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    <tr v-for="row in rows" :key="row.channel">
                        <td class="px-5 py-3 text-body-sm">
                            <span class="inline-flex items-center gap-2">
                                <span
                                    class="h-2.5 w-2.5 rounded-full"
                                    :style="{ backgroundColor: channelMeta(row.channel).color }"
                                />
                                {{ channelMeta(row.channel).label }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-body-sm text-right">
                            {{ Number(row.count ?? 0).toLocaleString(getIntlLocale()) }}
                        </td>
                        <td class="px-5 py-3 text-body-sm text-right">{{ num(row.avg_lead) }}</td>
                        <td class="px-5 py-3 text-body-sm text-right">{{ num(row.avg_los) }}</td>
                    </tr>
                </tbody>
                <tfoot class="bg-neutral-50 border-t-2 font-semibold">
                    <tr>
                        <td class="px-5 py-3 text-body-sm">{{ $t('admin.generated.k_648d30da28dc') }}</td>
                        <td class="px-5 py-3 text-body-sm text-right">
                            {{ Number(summary.count ?? 0).toLocaleString(getIntlLocale()) }}
                        </td>
                        <td class="px-5 py-3 text-body-sm text-right">{{ num(summary.avg_lead) }}</td>
                        <td class="px-5 py-3 text-body-sm text-right">{{ num(summary.avg_los) }}</td>
                    </tr>
                </tfoot>
            </table>
            <div v-else class="px-6 py-10 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_628aad7f2829') }}</div>
        </Card>
    </ReportShell>
</template>
