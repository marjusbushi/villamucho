<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import { money, sourceBadge } from './financeShared.js';

const props = defineProps({
    accounts: Array,
    receivables: Object,
    payables: Object,
    cashflow: Array,
    alerts: Array,
    latest: Array,
    baseCurrency: String,
    fxRate: Number,
    can: Object,
});

const maxFlow = Math.max(1, ...props.cashflow.map((d) => Math.max(d.in, d.out)));
const pct = (v) => Math.round((v / maxFlow) * 100);
</script>

<template>
    <AppLayout>
        <PageHeader title="Paneli financiar" :breadcrumbs="[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Financa' }, { label: 'Paneli' }]" />

        <div class="px-4 sm:px-6 pb-10 space-y-4">
            <div v-if="fxRate" class="text-tiny text-neutral-500">Monedha bazë: <b class="text-primary-900">EUR €</b> · Kursi: <b class="text-primary-900">1 € = {{ fxRate }} L</b></div>

            <!-- stats -->
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <Card v-for="a in accounts" :key="a.id">
                    <p class="text-tiny font-semibold text-neutral-500">{{ a.type === 'cash' ? '💵' : '🏦' }} {{ a.name }} <span class="text-neutral-400">({{ a.currency }})</span></p>
                    <p class="mt-1 text-h3 font-extrabold text-primary-900 tabular-nums">{{ money(a.balance, a.currency) }}</p>
                </Card>
                <Card>
                    <p class="text-tiny font-semibold text-neutral-500">Për t'u arkëtuar</p>
                    <p class="mt-1 text-h3 font-extrabold text-primary-900 tabular-nums">{{ money(receivables.total) }}</p>
                    <p class="text-tiny text-neutral-400">{{ receivables.count }} fatura të hapura</p>
                </Card>
                <Card>
                    <p class="text-tiny font-semibold text-neutral-500">Për t'u paguar</p>
                    <p class="mt-1 text-h3 font-extrabold tabular-nums" :class="payables.total > 0 ? 'text-error-600' : 'text-primary-900'">{{ money(payables.total) }}</p>
                    <p class="text-tiny text-neutral-400">{{ payables.count }} bills të hapura</p>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-[2fr,1fr]">
                <!-- cash-flow 14 ditë -->
                <Card>
                    <h3 class="text-tiny font-bold uppercase tracking-wide text-neutral-400 mb-3">Cash-flow — 14 ditët e fundit (EUR bazë)</h3>
                    <div class="flex items-end gap-1.5 h-32">
                        <div v-for="d in cashflow" :key="d.date" class="flex-1 flex flex-col justify-end gap-0.5" :title="`${d.date}: +${d.in} / -${d.out}`">
                            <div class="bg-accent-500/85 rounded-t" :style="{ height: pct(d.in) + '%' }" />
                            <div class="bg-error-400/60 rounded-b" :style="{ height: pct(d.out) + '%' }" />
                        </div>
                    </div>
                    <div class="flex gap-5 mt-2 text-tiny text-neutral-500">
                        <span><i class="inline-block w-2 h-2 rounded-sm bg-accent-500 mr-1.5 align-[0px]" />Hyrje</span>
                        <span><i class="inline-block w-2 h-2 rounded-sm bg-error-400 mr-1.5 align-[0px]" />Dalje</span>
                    </div>
                </Card>

                <!-- alerts -->
                <Card>
                    <h3 class="text-tiny font-bold uppercase tracking-wide text-neutral-400 mb-3">⚠ Kërkojnë vëmendje</h3>
                    <p v-if="!alerts.length" class="text-body-sm text-neutral-400">Asgjë urgjente sot. 👌</p>
                    <ul class="space-y-2.5">
                        <li v-for="(al, i) in alerts" :key="i" class="flex items-start justify-between gap-2 text-body-sm">
                            <span class="text-neutral-600 min-w-0">{{ al.label }}<span class="block text-tiny text-neutral-400 tabular-nums">{{ money(al.amount) }}</span></span>
                            <span class="shrink-0 text-tiny font-bold rounded-full px-2 py-0.5" :class="al.severity === 'error' ? 'bg-error-50 text-error-600' : 'bg-warning-50 text-warning-700'">{{ al.badge }}</span>
                        </li>
                    </ul>
                </Card>
            </div>

            <!-- latest -->
            <Card>
                <h3 class="text-tiny font-bold uppercase tracking-wide text-neutral-400 mb-2">Lëvizjet e fundit</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-body-sm tabular-nums">
                        <thead><tr class="text-tiny uppercase tracking-wide text-neutral-400 text-left border-b border-neutral-100">
                            <th class="py-2 pr-3">Data</th><th class="py-2 pr-3">Përshkrimi</th><th class="py-2 pr-3">Llogaria</th><th class="py-2 pr-3">Burimi</th><th class="py-2 text-right">Shuma</th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="p in latest" :key="p.id" class="border-b border-neutral-50 last:border-0">
                                <td class="py-2.5 pr-3 whitespace-nowrap text-neutral-500">{{ p.paid_at.slice(0, 16) }}</td>
                                <td class="py-2.5 pr-3 text-primary-900">{{ p.description }}</td>
                                <td class="py-2.5 pr-3 text-neutral-600">{{ p.direction === 'transfer' ? p.account + ' → ' + p.counter_account : p.account }}</td>
                                <td class="py-2.5 pr-3"><span class="text-tiny font-bold rounded-full px-2 py-0.5" :class="sourceBadge(p).cls">{{ sourceBadge(p).text }}</span></td>
                                <td class="py-2.5 text-right font-semibold whitespace-nowrap" :class="p.direction === 'in' ? 'text-accent-600' : p.direction === 'out' ? 'text-error-600' : 'text-neutral-500'">
                                    {{ p.direction === 'in' ? '+' : p.direction === 'out' ? '−' : '' }} {{ money(p.amount, p.currency) }}
                                </td>
                            </tr>
                            <tr v-if="!latest.length"><td colspan="5" class="py-6 text-center text-neutral-400">Ende pa lëvizje — ato hyjnë vetë nga folio dhe POS.</td></tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
