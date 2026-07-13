<script setup>
import { getIntlLocale, translate } from '@/i18n';
import ReportShell from '@/Components/UI/ReportShell.vue';
import Card from '@/Components/UI/Card.vue';
import ReportKpiGrid from '@/Components/UI/ReportKpiGrid.vue';
import ReportBarList from '@/Components/UI/ReportBarList.vue';
import { computed } from 'vue';
import { Banknote, BedDouble, CalendarClock, ReceiptText } from 'lucide-vue-next';

const props = defineProps({
    horizons: { type: Array, default: () => [] },
    next14: { type: Array, default: () => [] },
    currency: { type: String, default: '€' },
});

const money = (v) => `${props.currency}${Number(v ?? 0).toLocaleString(getIntlLocale(), { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const fmtDate = (d) => {
    if (!d) return '';
    const date = new Date(d + 'T00:00:00');
    return date.toLocaleDateString(getIntlLocale(), { weekday: 'short', day: '2-digit', month: 'short' });
};

const totals = () => ({
    bookings: props.next14.reduce((s, r) => s + Number(r.rooms ?? 0), 0),
    revenue: props.next14.reduce((s, r) => s + Number(r.revenue ?? 0), 0),
});

const kpis = [
    { label: translate('admin.generated.k_a31789f778d9'), value: () => totals().bookings, tone: 'accent', icon: ReceiptText },
    { label: translate('admin.generated.k_f22549b9f81a'), value: () => money(totals().revenue), tone: 'success', icon: Banknote },
    { label: translate('admin.generated.k_20c2e43aeb7d'), value: () => props.horizons.find((item) => item.days === 30)?.nights ?? 0, tone: 'info', icon: BedDouble },
    { label: translate('admin.generated.k_cebe684d1281'), value: () => money(props.horizons.find((item) => item.days === 30)?.adr), tone: 'neutral', icon: CalendarClock },
];

const horizonBars = computed(() => props.horizons.map((item) => ({
    key: item.days,
    label: translate('admin.generated.k_895b0d032665', { p0: item.days }),
    value: Number(item.revenue ?? 0),
    display: money(item.revenue),
    detail: translate('admin.generated.k_5c5e8acdbe88', { p0: item.bookings ?? 0, p1: item.nights ?? 0 }),
})));
</script>

<template>
    <ReportShell :title="$t('admin.generated.k_9cf5c4f3a868')" route-name="reports.pace" :filters="null">
        <ReportKpiGrid :items="kpis" />
        <ReportBarList class="mt-5" :title="$t('admin.generated.k_cd92ddaa690d')" :description="$t('admin.generated.k_2f117fc739e3')" :rows="horizonBars" />

        <!-- Horizon breakdown table -->
        <div class="mt-6">
            <Card :padding="false">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h3 class="text-label text-neutral-600 uppercase tracking-wider">{{ $t('admin.generated.k_6b4a845ccdfc') }}</h3>
                </div>
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_dcfacc0ca703') }}</th>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_0d3f1ad8a61e') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_23e101b53dfc') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_554f99c760d5') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_df60b741d582') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_940e96afc111') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr v-for="h in horizons" :key="h.days" class="hover:bg-neutral-50">
                            <td class="px-5 py-3 text-body-sm text-primary-900 font-medium">{{ h.days }} {{ $t('admin.generated.k_0333f1e447a7') }}</td>
                            <td class="px-5 py-3 text-body-sm text-neutral-600">{{ fmtDate(h.until) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ h.bookings }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ h.nights }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-primary-900">{{ money(h.revenue) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-600">{{ money(h.adr) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!horizons.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_800fa54b58d0') }}</div>
            </Card>
        </div>

        <!-- Next 14 days arrivals/occupancy -->
        <div class="mt-6">
            <Card :padding="false">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h3 class="text-label text-neutral-600 uppercase tracking-wider">{{ $t('admin.generated.k_cdcc03ab7569') }}</h3>
                </div>
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_bf86e0880891') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_759f45ff4971') }}</th>
                            <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_88ebed50dc9e') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr v-for="row in next14" :key="row.date" class="hover:bg-neutral-50">
                            <td class="px-5 py-3 text-body-sm text-neutral-700 capitalize">{{ fmtDate(row.date) }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ row.rooms }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-primary-900">{{ money(row.revenue) }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="next14.length" class="bg-neutral-50 border-t-2 border-neutral-200">
                        <tr class="font-semibold">
                            <td class="px-5 py-3 text-body-sm text-neutral-700">{{ $t('admin.generated.k_fb29c5870bc7') }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-neutral-700">{{ totals().bookings }}</td>
                            <td class="px-5 py-3 text-right text-body-sm text-primary-900">{{ money(totals().revenue) }}</td>
                        </tr>
                    </tfoot>
                </table>
                <div v-if="!next14.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_800fa54b58d0') }}</div>
            </Card>
        </div>
    </ReportShell>
</template>
