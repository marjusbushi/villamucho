<script setup>
import { getIntlLocale, translate } from '@/i18n';
import ReportShell from '@/Components/UI/ReportShell.vue';
import Card from '@/Components/UI/Card.vue';
import ReportKpiGrid from '@/Components/UI/ReportKpiGrid.vue';
import ReportBarList from '@/Components/UI/ReportBarList.vue';
import { computed } from 'vue';
import { Banknote, CreditCard, Hotel, WalletCards } from 'lucide-vue-next';

const props = defineProps({
    filters: Object,
    rows: { type: Array, default: () => [] },
    byMethod: { type: Array, default: () => [] },
    totals: Object,
    currency: { type: String, default: '€' },
});

const money = (v) => `${props.currency}${Number(v ?? 0).toLocaleString(getIntlLocale(), { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

function fmtDate(d) {
    if (!d) return '—';
    const [y, m, day] = String(d).split('-');
    return `${day}/${m}/${y}`;
}

const kpis = [
    { label: translate('admin.generated.k_2d93106df04b'), value: () => money(props.totals?.total), tone: 'accent', icon: WalletCards, detail: translate('admin.generated.k_3a8ce5e851c1') },
    { label: translate('admin.generated.k_b085dc8c57c6'), value: () => money(props.totals?.cash), tone: 'success', icon: Banknote },
    { label: translate('admin.generated.k_d497ddc10f8a'), value: () => money(props.totals?.card), tone: 'info', icon: CreditCard },
    { label: translate('admin.generated.k_8d89610855d5'), value: () => money(props.totals?.room_charge), tone: 'neutral', icon: Hotel },
];

const methodBars = computed(() => props.byMethod.map((method) => ({
    key: method.method,
    label: method.label,
    value: Number(method.amount ?? 0),
    display: money(method.amount),
    barClass: method.method === 'cash' ? 'bg-success-500' : method.method === 'card' ? 'bg-info-500' : 'bg-accent-500',
})));
</script>

<template>
    <ReportShell :title="$t('admin.generated.k_3b73e7a1bf6c')" route-name="reports.payments" :filters="filters">
        <ReportKpiGrid :items="kpis" />

        <p class="text-body-sm text-neutral-500 mt-2 mb-4">
{{ $t('admin.generated.k_8ffcf8b2ef20') }} <span class="font-medium text-neutral-700">{{ $t('admin.generated.k_29d9ba2baecc') }}</span> {{ $t('admin.generated.k_884ea0490f40') }} </p>

        <!-- Collected by method -->
        <div class="mb-4 grid gap-4 xl:grid-cols-[minmax(280px,0.65fr)_1.35fr]">
            <ReportBarList :title="$t('admin.generated.k_e50cfb0dfe92')" :description="$t('admin.generated.k_f215967be197')" :rows="methodBars" />
            <Card :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_91ae5c2d75a4') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_241513d096f4') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr v-for="m in byMethod" :key="m.method" class="hover:bg-neutral-50">
                            <td class="px-5 py-3 text-body-sm text-primary-900 font-medium">{{ m.label }}</td>
                            <td class="px-5 py-3 text-right text-body-sm" :class="m.method === 'cash' ? 'text-success-700' : 'text-neutral-700'">{{ money(m.amount) }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="byMethod.length" class="bg-neutral-50 border-t-2 border-neutral-200">
                        <tr>
                            <td class="px-5 py-3 text-body-sm font-semibold text-primary-900">{{ $t('admin.generated.k_fe38cb6ba925') }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold text-primary-900">{{ money(totals?.total) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div v-if="!byMethod.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_0db22e3986b9') }}</div>
            </Card>
        </div>

        <!-- Per-day breakdown -->
        <Card :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_6c92798ec3bb') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_8987f4126a98') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_b75b6369ad66') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_632bd56bfe36') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_8b26d0c1c34a') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr v-for="r in rows" :key="r.date" class="hover:bg-neutral-50">
                            <td class="px-5 py-3 text-body-sm text-primary-900 font-medium whitespace-nowrap">{{ fmtDate(r.date) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-success-700">{{ money(r.payments_cash) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ money(r.payments_card) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ money(r.pos_total) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-primary-900 font-medium">{{ money(r.total) }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="rows.length" class="bg-neutral-50 border-t-2 border-neutral-200">
                        <tr>
                            <td class="px-5 py-3 text-body-sm font-semibold text-primary-900">{{ $t('admin.generated.k_41efb24579a9') }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold text-success-700">{{ money(totals?.payments_cash) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold">{{ money(totals?.payments_card) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold">{{ money(totals?.pos_total) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm font-semibold text-primary-900">{{ money(totals?.total) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div v-if="!rows.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_0db22e3986b9') }}</div>
        </Card>
    </ReportShell>
</template>
