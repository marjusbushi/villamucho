<script setup>
import { translate } from '@/i18n';
import ReportShell from '@/Components/UI/ReportShell.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import ReportKpiGrid from '@/Components/UI/ReportKpiGrid.vue';
import { CheckCircle2, ClipboardList, Clock3, Gauge } from 'lucide-vue-next';

const props = defineProps({
    filters: Object,
    byStaff: { type: Array, default: () => [] },
    recent: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    currency: { type: String, default: '€' },
});

const fmtDate = (d) => {
    if (!d) return '—';
    const [y, m, day] = String(d).split('-');
    return `${day}/${m}/${y}`;
};

const statusMeta = (s) => ({
    pending: { label: translate('admin.generated.k_69bf17a8c913'), variant: 'warning' },
    in_progress: { label: translate('admin.generated.k_f71b4f9e49d2'), variant: 'info' },
    completed: { label: translate('admin.generated.k_fd95c2517d36'), variant: 'success' },
    inspected: { label: translate('admin.generated.k_315ee819707b'), variant: 'accent' },
}[s] || { label: s || '—', variant: 'neutral' });

const typeLabel = (t) => ({
    checkout: 'Largim',
    daily: 'Ditore',
    deep: translate('admin.generated.k_45856440793a'),
    maintenance: translate('admin.generated.k_a195cbcf25e8'),
    turndown: translate('admin.generated.k_225c6e2917cb'),
}[t] || t || '—');

const kpis = [
    { label: translate('admin.generated.k_920044109739'), value: () => props.summary.total ?? 0, tone: 'accent', icon: ClipboardList },
    { label: translate('admin.generated.k_fd95c2517d36'), value: () => props.summary.completed ?? 0, tone: 'success', icon: CheckCircle2 },
    { label: translate('admin.generated.k_69bf17a8c913'), value: () => props.summary.pending ?? 0, tone: 'warning', icon: Clock3 },
    { label: translate('admin.generated.k_d55f1b650495'), value: () => props.summary.total ? `${Math.round((Number(props.summary.completed ?? 0) / Number(props.summary.total)) * 100)}%` : '0%', tone: 'info', icon: Gauge },
];
</script>

<template>
    <ReportShell :title="$t('admin.generated.k_1e6b1fc8a4b0')" route-name="reports.housekeepingReport" :filters="filters">
        <ReportKpiGrid :items="kpis" />

        <div class="mt-6">
            <Card :padding="false">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h3 class="text-body font-semibold text-primary-900">{{ $t('admin.generated.k_bc0e4ddf6176') }}</h3>
                </div>
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_d9d7ce0a9afb') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_1cae4729a117') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_e26d75a98bfa') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_9fc3a7123668') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr v-for="row in byStaff" :key="row.staff" class="hover:bg-neutral-50">
                            <td class="px-5 py-3 text-body-sm text-primary-900 font-medium">{{ row.staff }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ row.total }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-success-700 font-medium">{{ row.completed }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ row.pending }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="byStaff.length" class="bg-neutral-50 border-t-2 border-neutral-200">
                        <tr class="font-semibold text-neutral-800">
                            <td class="px-5 py-3 text-body-sm">{{ $t('admin.generated.k_1d09bb6ec23f') }}</td>
                            <td class="px-5 py-3 text-right text-body-sm">{{ summary.total ?? byStaff.reduce((s, r) => s + (r.total || 0), 0) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm">{{ summary.completed ?? byStaff.reduce((s, r) => s + (r.completed || 0), 0) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm">{{ summary.pending ?? byStaff.reduce((s, r) => s + (r.pending || 0), 0) }}</td>
                        </tr>
                    </tfoot>
                </table>
                <div v-if="!byStaff.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_c4049d88275b') }}</div>
            </Card>
        </div>

        <div class="mt-6">
            <Card :padding="false">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h3 class="text-body font-semibold text-primary-900">{{ $t('admin.generated.k_cf3050ba3837') }}</h3>
                </div>
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_b0b6bea4b72c') }}</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_f624ccfa0287') }}</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_4611eaaae399') }}</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_e4aa7831f80b') }}</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_2afedfaa0a90') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_dd0a882a4f0f') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr v-for="row in recent" :key="row.id" class="hover:bg-neutral-50">
                            <td class="px-5 py-3 text-body-sm text-primary-900 font-medium">{{ row.room }}</td>
                            <td class="px-5 py-3 text-body-sm text-neutral-700">{{ typeLabel(row.type) }}</td>
                            <td class="px-5 py-3 text-body-sm">
                                <Badge :variant="statusMeta(row.status).variant">{{ statusMeta(row.status).label }}</Badge>
                            </td>
                            <td class="px-5 py-3 text-body-sm">
                                <Badge v-if="row.priority === 'urgent'" variant="error">{{ $t('admin.generated.k_29a027a05002') }}</Badge>
                                <span v-else class="text-neutral-500">{{ $t('admin.generated.k_e294090ba0f8') }}</span>
                            </td>
                            <td class="px-5 py-3 text-body-sm text-neutral-700">{{ row.assigned }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ fmtDate(row.created) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!recent.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_c4049d88275b') }}</div>
            </Card>
        </div>
    </ReportShell>
</template>
