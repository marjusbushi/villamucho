<script setup>
import { getIntlLocale, translate } from '@/i18n';
import ReportShell from '@/Components/UI/ReportShell.vue';
import Card from '@/Components/UI/Card.vue';
import ReportKpiGrid from '@/Components/UI/ReportKpiGrid.vue';
import { channelMeta } from '@/channels';
import { AlertTriangle, Ban, CirclePercent, WalletCards } from 'lucide-vue-next';

const props = defineProps({
    filters: Object,
    summary: Object,
    cancelled: { type: Array, default: () => [] },
    noShows: { type: Array, default: () => [] },
    currency: { type: String, default: '€' },
});

const money = (v) => `${props.currency}${Number(v ?? 0).toLocaleString(getIntlLocale(), { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const kpis = [
    { label: translate('admin.generated.k_581e3d2b2b2c'), value: () => props.summary.cancelled_count, tone: 'error', icon: Ban, detail: () => translate('admin.generated.k_5e70afe76611', { p0: props.summary.total_count ?? 0 }) },
    { label: translate('admin.generated.k_c412a083ce91'), value: () => money(props.summary.cancelled_value), tone: 'warning', icon: WalletCards },
    { label: translate('admin.generated.k_1d9bc2b2cc4c'), value: () => `${props.summary.cancellation_rate}%`, tone: 'neutral', icon: CirclePercent },
    { label: translate('admin.generated.k_03fa6d973c70'), value: () => props.summary.no_show_count, tone: 'warning', icon: AlertTriangle, detail: translate('admin.generated.k_8f574a50a98a') },
];
</script>

<template>
    <ReportShell :title="$t('admin.generated.k_550df48e24f4')" route-name="reports.cancellations" :filters="filters">
        <ReportKpiGrid :items="kpis" />

        <p class="mt-2 text-tiny text-neutral-500">
{{ $t('admin.generated.k_bfd764d19373') }} {{ summary.total_count }} {{ $t('admin.generated.k_f440b77b5da8') }} </p>

        <!-- Cancelled reservations -->
        <div class="mt-6">
            <Card :padding="false">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h3 class="text-label text-neutral-600 uppercase tracking-wider">{{ $t('admin.generated.k_557cd711c683') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_f45e7b11aa3b') }}</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_172eb9a7077b') }}</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_625c03c01224') }}</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_d2397ee6b191') }}</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_557da31568c4') }}</th>
                                <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_07f3d33b71ac') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            <tr v-for="r in cancelled" :key="r.id" class="hover:bg-neutral-50">
                                <td class="px-5 py-3 text-body-sm text-primary-900">{{ r.guest }}</td>
                                <td class="px-5 py-3 text-body-sm text-neutral-700">{{ r.room || '—' }}</td>
                                <td class="px-5 py-3 text-body-sm text-neutral-700">{{ r.check_in }}</td>
                                <td class="px-5 py-3 text-body-sm text-neutral-700">{{ r.check_out }}</td>
                                <td class="px-5 py-3 text-body-sm">
                                    <span class="inline-flex items-center gap-2 text-primary-900">
                                        <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: channelMeta(r.channel).color }" />
                                        {{ channelMeta(r.channel).label }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right text-body-sm text-error-600 font-medium">{{ money(r.value) }}</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="cancelled.length" class="bg-neutral-50 border-t-2 border-neutral-200">
                            <tr>
                                <td class="px-5 py-3 text-body-sm font-semibold text-primary-900" colspan="5">{{ $t('admin.generated.k_d4622b308f8e') }}{{ summary.cancelled_count }})</td>
                                <td class="px-5 py-3 text-right text-body-sm font-semibold text-error-600">{{ money(summary.cancelled_value) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div v-if="!cancelled.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_12e0d88111a7') }}</div>
            </Card>
        </div>

        <!-- No-show candidates -->
        <div class="mt-6">
            <Card :padding="false">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h3 class="text-label text-neutral-600 uppercase tracking-wider">{{ $t('admin.generated.k_e0ea3b1cc161') }}</h3>
                    <p class="mt-1 text-tiny text-neutral-500">
{{ $t('admin.generated.k_c2ed01570b95') }} </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_f45e7b11aa3b') }}</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_172eb9a7077b') }}</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_625c03c01224') }}</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_d2397ee6b191') }}</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">{{ $t('admin.generated.k_557da31568c4') }}</th>
                                <th class="px-5 py-3 text-right text-label text-neutral-600">{{ $t('admin.generated.k_07f3d33b71ac') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            <tr v-for="r in noShows" :key="r.id" class="hover:bg-neutral-50">
                                <td class="px-5 py-3 text-body-sm text-primary-900">{{ r.guest }}</td>
                                <td class="px-5 py-3 text-body-sm text-neutral-700">{{ r.room || '—' }}</td>
                                <td class="px-5 py-3 text-body-sm text-neutral-700">{{ r.check_in }}</td>
                                <td class="px-5 py-3 text-body-sm text-neutral-700">{{ r.check_out }}</td>
                                <td class="px-5 py-3 text-body-sm">
                                    <span class="inline-flex items-center gap-2 text-primary-900">
                                        <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: channelMeta(r.channel).color }" />
                                        {{ channelMeta(r.channel).label }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right text-body-sm text-primary-900 font-medium">{{ money(r.value) }}</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="noShows.length" class="bg-neutral-50 border-t-2 border-neutral-200">
                            <tr>
                                <td class="px-5 py-3 text-body-sm font-semibold text-primary-900" colspan="5">{{ $t('admin.generated.k_d4622b308f8e') }}{{ summary.no_show_count }})</td>
                                <td class="px-5 py-3 text-right text-body-sm font-semibold text-primary-900">{{ money(summary.no_show_value) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div v-if="!noShows.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">{{ $t('admin.generated.k_09c07b2885bf') }}</div>
            </Card>
        </div>
    </ReportShell>
</template>
