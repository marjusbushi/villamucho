<script setup>
import { getIntlLocale, translate } from '@/i18n';
import { Link } from '@inertiajs/vue3';
import ReportShell from '@/Components/UI/ReportShell.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import ReportKpiGrid from '@/Components/UI/ReportKpiGrid.vue';
import { CirclePercent, RefreshCcw, Users } from 'lucide-vue-next';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    currency: { type: String, default: '€' },
});

const money = (v) => `${props.currency}${Number(v ?? 0).toLocaleString(getIntlLocale(), { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const fmtDate = (d) => {
    if (!d) return '—';
    const [y, m, day] = String(d).split('-');
    return `${day}/${m}/${y}`;
};

const kpis = [
    { label: translate('admin.generated.k_7f55392b936a'), value: () => props.summary.total_guests ?? 0, tone: 'neutral', icon: Users },
    { label: translate('admin.generated.k_f87d8e61bce0'), value: () => props.summary.repeat_guests ?? 0, tone: 'success', icon: RefreshCcw, detail: translate('admin.generated.k_1e3573673169') },
    { label: translate('admin.generated.k_7888ea6be2ae'), value: () => `${Number(props.summary.repeat_rate ?? 0).toLocaleString(getIntlLocale())}%`, tone: 'accent', icon: CirclePercent },
];
</script>

<template>
    <ReportShell :title="$t('admin.generated.k_0a1f8ff63639')" :filters="null">
        <ReportKpiGrid :items="kpis" />

        <div class="mt-6">
            <Card :padding="false">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_909542b47528') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_a060cd1136ed') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_d1341a62204c') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_36355badfb08') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_53ed2359db1e') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr v-for="row in rows" :key="row.id" class="hover:bg-neutral-50">
                            <td class="px-5 py-3 text-body-sm">
                                <div class="flex items-center gap-2">
                                    <Link :href="route('guests.show', row.id)" class="text-primary-900 font-medium hover:underline">{{ row.guest }}</Link>
                                    <Badge v-if="row.is_repeat" color="emerald">{{ $t('admin.generated.k_a7b286dbe2f5') }}</Badge>
                                </div>
                                <p v-if="row.email" class="text-tiny text-neutral-500">{{ row.email }}</p>
                            </td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ row.stays }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ row.nights }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-primary-900 font-medium">{{ money(row.total_spent) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ fmtDate(row.last_visit) }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="rows.length" class="bg-neutral-50 border-t-2 border-neutral-200">
                        <tr class="font-semibold text-neutral-800">
                            <td class="px-5 py-3 text-body-sm">{{ $t('admin.generated.k_df41e805e29b') }}{{ rows.length }} {{ $t('admin.generated.k_a6083ce12a69') }}</td>
                            <td class="px-5 py-3 text-right text-body-sm">{{ rows.reduce((s, r) => s + (r.stays || 0), 0) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm">{{ rows.reduce((s, r) => s + (r.nights || 0), 0) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm">{{ money(rows.reduce((s, r) => s + (r.total_spent || 0), 0)) }}</td>
                            <td class="px-5 py-3"></td>
                        </tr>
                    </tfoot>
                </table>
                <div v-if="!rows.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_71b0e3fc4997') }}</div>
            </Card>
        </div>
    </ReportShell>
</template>
