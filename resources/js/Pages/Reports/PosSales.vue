<script setup>
import { getIntlLocale, translate } from '@/i18n';
import ReportShell from '@/Components/UI/ReportShell.vue';
import Card from '@/Components/UI/Card.vue';
import ReportKpiGrid from '@/Components/UI/ReportKpiGrid.vue';
import ReportBarList from '@/Components/UI/ReportBarList.vue';
import { computed } from 'vue';
import { Banknote, CalendarDays, ReceiptText, ShoppingBasket } from 'lucide-vue-next';

const props = defineProps({
    filters: { type: Object, default: null },
    byCategory: { type: Array, default: () => [] },
    topItems: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    currency: { type: String, default: '€' },
});

const money = (v) => `${props.currency}${Number(v ?? 0).toLocaleString(getIntlLocale(), { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const qty = (v) => Number(v ?? 0).toLocaleString(getIntlLocale());

const catTotalQty = () => props.byCategory.reduce((s, r) => s + Number(r.qty ?? 0), 0);
const catTotalRevenue = () => props.byCategory.reduce((s, r) => s + Number(r.revenue ?? 0), 0);

const kpis = [
    { label: translate('admin.generated.k_abb5dc7bf21c'), value: () => money(props.summary.total_revenue), tone: 'accent', icon: Banknote },
    { label: translate('admin.generated.k_6babbab568ac'), value: () => qty(props.summary.order_count), tone: 'info', icon: ReceiptText },
    { label: translate('admin.generated.k_ae2aa29a256e'), value: () => money(props.summary.avg_ticket), tone: 'success', icon: ShoppingBasket },
    { label: translate('admin.generated.k_03f9944cb72c'), value: () => qty(props.summary.days), tone: 'neutral', icon: CalendarDays },
];

const categoryBars = computed(() => props.byCategory.map((row) => ({
    key: row.category,
    label: row.category,
    value: Number(row.revenue ?? 0),
    display: money(row.revenue),
    detail: `${qty(row.qty)} artikuj`,
})));
</script>

<template>
    <ReportShell :title="$t('admin.generated.k_22be4e2a259c')" route-name="reports.posSales" :filters="filters">
        <ReportKpiGrid :items="kpis" />

        <ReportBarList class="mt-5" :title="$t('admin.generated.k_92d95fab92cc')" :description="$t('admin.generated.k_918945590fd8')" :rows="categoryBars" />

        <!-- By category -->
        <Card :padding="false" class="mt-6">
            <div class="px-5 py-4 border-b border-neutral-200">
                <h3 class="text-h4 text-primary-900">{{ $t('admin.generated.k_36b4a963aaba') }}</h3>
            </div>
            <table v-if="byCategory.length" class="min-w-full divide-y divide-neutral-200">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_48449125494d') }}</th>
                        <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_80d6516576b5') }}</th>
                        <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_ac1339756b5c') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    <tr v-for="row in byCategory" :key="row.category">
                        <td class="px-5 py-3 text-body-sm">{{ row.category }}</td>
                        <td class="px-5 py-3 text-body-sm text-right">{{ qty(row.qty) }}</td>
                        <td class="px-5 py-3 text-body-sm text-right">{{ money(row.revenue) }}</td>
                    </tr>
                </tbody>
                <tfoot class="bg-neutral-50 border-t-2 border-neutral-200">
                    <tr class="font-semibold">
                        <td class="px-5 py-3 text-body-sm">{{ $t('admin.generated.k_797d3c41869c') }}</td>
                        <td class="px-5 py-3 text-body-sm text-right">{{ qty(catTotalQty()) }}</td>
                        <td class="px-5 py-3 text-body-sm text-right">{{ money(catTotalRevenue()) }}</td>
                    </tr>
                </tfoot>
            </table>
            <div v-else class="px-6 py-10 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_691be38c1767') }}</div>
        </Card>

        <!-- Top items -->
        <Card :padding="false" class="mt-6">
            <div class="px-5 py-4 border-b border-neutral-200">
                <h3 class="text-h4 text-primary-900">{{ $t('admin.generated.k_401fbbbfd15d') }}</h3>
            </div>
            <table v-if="topItems.length" class="min-w-full divide-y divide-neutral-200">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-label text-neutral-600">#</th>
                        <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_4877e3f9d0f5') }}</th>
                        <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_48449125494d') }}</th>
                        <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_80d6516576b5') }}</th>
                        <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_ac1339756b5c') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    <tr v-for="(row, i) in topItems" :key="row.item + i">
                        <td class="px-5 py-3 text-body-sm text-neutral-500">{{ i + 1 }}</td>
                        <td class="px-5 py-3 text-body-sm">{{ row.item }}</td>
                        <td class="px-5 py-3 text-body-sm text-neutral-600">{{ row.category }}</td>
                        <td class="px-5 py-3 text-body-sm text-right">{{ qty(row.qty) }}</td>
                        <td class="px-5 py-3 text-body-sm text-right">{{ money(row.revenue) }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="px-6 py-10 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_691be38c1767') }}</div>
        </Card>
    </ReportShell>
</template>
