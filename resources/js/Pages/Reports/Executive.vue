<script setup>
import { getIntlLocale, translate } from '@/i18n';
import ReportShell from '@/Components/UI/ReportShell.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import ReportKpiGrid from '@/Components/UI/ReportKpiGrid.vue';
import ReportBarList from '@/Components/UI/ReportBarList.vue';
import { computed } from 'vue';
import { Banknote, BedDouble, ChartNoAxesCombined, Percent } from 'lucide-vue-next';

const props = defineProps({
    filters: Object,
    summary: Object,
    byStatus: { type: Array, default: () => [] },
    currency: { type: String, default: '€' },
});

const money = (v) => `${props.currency}${Number(v ?? 0).toLocaleString(getIntlLocale(), { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const statusBadge = {
    pending: { variant: 'warning', label: translate('admin.generated.k_0558724d340e') },
    confirmed: { variant: 'info', label: translate('admin.generated.k_ff8a5dbeca00') },
    checked_in: { variant: 'success', label: translate('admin.generated.k_cb2d5eeef805') },
    checked_out: { variant: 'neutral', label: translate('admin.generated.k_ed59e688d442') },
    cancelled: { variant: 'error', label: translate('admin.generated.k_6900524ce6d5') },
};

const primaryKpis = [
    { label: translate('admin.generated.k_de2b8dcabcc0'), value: () => money(props.summary.total_revenue), tone: 'accent', icon: Banknote, detail: translate('admin.generated.k_a385c33ba612') },
    { label: translate('admin.generated.k_cab410b8f608'), value: () => `${props.summary.occupancy}%`, tone: 'info', icon: BedDouble, detail: translate('admin.generated.k_22e92db4ffe7', { p0: props.summary.nights_sold ?? 0 }) },
    { label: translate('admin.generated.k_fde1c8a882f7'), value: () => money(props.summary.adr), tone: 'neutral', icon: ChartNoAxesCombined, detail: translate('admin.generated.k_3575f5c4484c') },
    { label: translate('admin.generated.k_20be469f857d'), value: () => money(props.summary.revpar), tone: 'success', icon: Percent, detail: translate('admin.generated.k_70b947196445') },
];

const secondaryKpis = [
    { label: translate('admin.generated.k_eae61dfbcec3'), value: () => props.summary.reservation_count },
    { label: translate('admin.generated.k_511779cab80e'), value: () => money(props.summary.commission), tone: 'warning' },
    { label: translate('admin.generated.k_d2d61854cb1f'), value: () => money(props.summary.net_room_revenue), tone: 'success' },
    { label: translate('admin.generated.k_e9d6de4b452b'), value: () => money(props.summary.vat) },
];

const revenueMix = computed(() => [
    { key: 'rooms', label: translate('admin.generated.k_c6eebfc53925'), value: Number(props.summary.room_revenue ?? 0), display: money(props.summary.room_revenue), barClass: 'bg-accent-600' },
    { key: 'pos', label: translate('admin.generated.k_be0a1de9dccd'), value: Number(props.summary.pos_revenue ?? 0), display: money(props.summary.pos_revenue), barClass: 'bg-info-500' },
]);
</script>

<template>
    <ReportShell :title="$t('admin.generated.k_f5158f1acd78')" route-name="reports.executive" :filters="filters">
        <ReportKpiGrid :items="primaryKpis" />

        <div class="mt-4">
            <ReportKpiGrid :items="secondaryKpis" />
        </div>

        <div class="mt-6 grid gap-4 xl:grid-cols-[minmax(280px,0.7fr)_1.3fr]">
            <ReportBarList
                :title="$t('admin.generated.k_3ad877197e4e')"
                :description="$t('admin.generated.k_edcec4e6685b')"
                :rows="revenueMix"
            />
            <Card :padding="false">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h3 class="text-label text-neutral-600 uppercase tracking-wider">{{ $t('admin.generated.k_3e50f506e50e') }}</h3>
                </div>
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_c631619d3aef') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_f571bccca1cc') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_dc526783ac5e') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr v-for="row in byStatus" :key="row.status" class="hover:bg-neutral-50">
                            <td class="px-5 py-3"><Badge :variant="statusBadge[row.status]?.variant || 'neutral'">{{ statusBadge[row.status]?.label || row.status }}</Badge></td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ row.count }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-primary-900">{{ money(row.revenue) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!byStatus.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_e0d482615ccd') }}</div>
            </Card>
        </div>
    </ReportShell>
</template>
