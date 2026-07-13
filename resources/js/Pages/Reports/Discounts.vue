<script setup>
import { getIntlLocale, translate } from '@/i18n';
import { Link } from '@inertiajs/vue3';
import ReportShell from '@/Components/UI/ReportShell.vue';
import Card from '@/Components/UI/Card.vue';
import ReportKpiGrid from '@/Components/UI/ReportKpiGrid.vue';
import { CirclePercent, ReceiptText } from 'lucide-vue-next';

const props = defineProps({
    filters: Object,
    rows: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
    currency: { type: String, default: '€' },
});

const money = (v) => `${props.currency}${Number(v ?? 0).toLocaleString(getIntlLocale(), { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const fmt = (d) => d ? new Date(d).toLocaleDateString(getIntlLocale(), { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

const kpis = [
    { label: translate('admin.generated.k_7d7c3d8cf59a'), value: () => money(props.total), tone: 'warning', icon: CirclePercent, detail: translate('admin.generated.k_5e647bde93cd') },
    { label: translate('admin.generated.k_68380b30a8e5'), value: () => props.rows.length, tone: 'neutral', icon: ReceiptText },
];
</script>

<template>
    <ReportShell :title="$t('admin.generated.k_e1727c5d2583')" route-name="reports.discounts" :filters="filters">
        <ReportKpiGrid :items="kpis" />

        <Card :padding="false" class="mt-5">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_50366838fb70') }}</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_3dc089a1e4d7') }}</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_0140ed98479e') }}</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_844bb423b643') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_8f9b96778f44') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr v-for="r in rows" :key="r.id" class="hover:bg-neutral-50">
                            <td class="px-5 py-3 text-body-sm text-neutral-500 whitespace-nowrap">{{ fmt(r.date) }}</td>
                            <td class="px-5 py-3 text-body-sm">
                                <Link v-if="r.reservation_id" :href="route('reservations.show', r.reservation_id)" class="text-primary-900 font-medium hover:underline">{{ r.guest }}</Link>
                                <span v-else class="text-primary-900">{{ r.guest }}</span>
                            </td>
                            <td class="px-5 py-3 text-body-sm text-neutral-700">{{ r.room || '—' }}</td>
                            <td class="px-5 py-3 text-body-sm text-neutral-700">{{ r.description || '—' }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-medium text-warning-600">−{{ money(r.amount) }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="rows.length" class="bg-neutral-50 border-t-2 border-neutral-200">
                        <tr>
                            <td class="px-5 py-3 text-body-sm font-semibold text-primary-900" colspan="4">{{ $t('admin.generated.k_ae8a9088aea4') }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold text-warning-600">−{{ money(total) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div v-if="!rows.length" class="px-6 py-12 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_03d4e4dee89a') }}</div>
        </Card>
    </ReportShell>
</template>
