<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import {
    ArrowRight,
    Building2,
    Check,
    CreditCard,
    Gauge,
    Layers3,
    RefreshCw,
    Users,
} from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    moduleAdoption: Array,
    needsAttention: Array,
    recentTenants: Array,
});

const refreshing = ref(false);

function ratio(value, total) {
    return total > 0 ? Math.round((value / total) * 100) : 100;
}

const kpis = computed(() => [
    {
        label: 'Hotele aktive',
        value: props.stats.hotels_active,
        lead: `${ratio(props.stats.hotels_active, props.stats.hotels_total)}%`,
        leadClass: 'text-emerald-700',
        detail: 'e portofolit aktiv',
        icon: Building2,
        tone: 'emerald',
    },
    {
        label: 'MRR i parashikuar',
        value: money(props.stats.mrr_cents),
        detail: 'Pa tarifat variabël 1%',
        icon: CreditCard,
        tone: 'blue',
    },
    {
        label: 'Abonime aktive',
        value: props.stats.subscriptions_active,
        lead: props.stats.subscriptions_attention,
        leadClass: props.stats.subscriptions_attention ? 'text-amber-700' : 'text-emerald-700',
        detail: 'me pagesë të vonuar',
        icon: Layers3,
        tone: 'emerald',
    },
    {
        label: 'Përdorues',
        value: props.stats.users_total,
        detail: 'Në të gjitha hotelet',
        icon: Users,
        tone: 'violet',
    },
]);

const signals = computed(() => [
    {
        label: 'Abonimet',
        value: props.stats.subscriptions_active,
        total: props.stats.hotels_total,
        tone: props.stats.subscriptions_attention ? 'attention' : 'ok',
    },
    {
        label: 'Domain-et',
        value: props.stats.domains_configured,
        total: props.stats.hotels_total,
        tone: props.stats.domains_configured === props.stats.hotels_total ? 'ok' : 'attention',
    },
    {
        label: 'Integrimet',
        value: props.stats.integrations_ready,
        total: props.stats.integrations_total,
        tone: props.stats.integrations_ready === props.stats.integrations_total ? 'ok' : 'attention',
    },
]);

const healthLabel = computed(() => {
    if (props.stats.health_score >= 85) return 'Gjendje shumë e mirë';
    if (props.stats.health_score >= 65) return 'Gjendje e mirë';
    return 'Kërkon vëmendje';
});

const healthDetail = computed(() => props.stats.subscriptions_attention
    ? 'Disa abonime kërkojnë ndërhyrje.'
    : 'Abonimet janë aktive; plotëso domain-et dhe integrimet e mbetura.');

function toneClass(tone) {
    return {
        emerald: 'bg-emerald-50 text-emerald-700',
        blue: 'bg-blue-50 text-blue-700',
        violet: 'bg-violet-50 text-violet-700',
    }[tone] || 'bg-neutral-100 text-neutral-600';
}

function money(cents) {
    return new Intl.NumberFormat('sq-AL', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
    }).format((cents || 0) / 100);
}

function date(value) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('sq-AL', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value));
}

function statusLabel(status) {
    return {
        trialing: 'Provë', active: 'Aktiv', past_due: 'Pagesë e vonuar',
        suspended: 'Pezulluar', canceled: 'Anuluar', inactive: 'Joaktiv',
    }[status] || status;
}

function statusClass(status) {
    if (status === 'past_due') return 'bg-amber-50 text-amber-700';
    if (['suspended', 'canceled', 'inactive'].includes(status)) return 'bg-red-50 text-red-700';
    return 'bg-emerald-50 text-emerald-700';
}

function statusDotClass(status) {
    if (status === 'past_due') return 'bg-amber-500';
    if (['suspended', 'canceled', 'inactive'].includes(status)) return 'bg-red-500';
    return 'bg-emerald-500';
}

function attentionClass(severity) {
    return {
        danger: 'border-red-200 bg-red-50 text-red-700',
        warning: 'border-amber-200 bg-amber-50 text-amber-700',
        info: 'border-neutral-200 bg-neutral-50 text-neutral-600',
    }[severity] || 'border-neutral-200 bg-neutral-50 text-neutral-600';
}

function refresh() {
    router.reload({
        only: ['stats', 'moduleAdoption', 'needsAttention', 'recentTenants'],
        onStart: () => { refreshing.value = true; },
        onFinish: () => { refreshing.value = false; },
    });
}
</script>

<template>
    <SuperAdminLayout title="Përmbledhje — Lora Control Panel">
        <div class="mx-auto max-w-[1480px] space-y-5">
            <div>
                <div class="text-xs text-neutral-400">
                    <span>Control Panel</span><span class="mx-2">/</span><span>Përmbledhje</span>
                </div>
                <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-semibold tracking-tight text-neutral-950">Përmbledhje</h1>
                        <p class="mt-2 text-sm text-neutral-500">Gjendja operative dhe financiare e platformës sot.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 py-2.5 text-sm font-semibold text-neutral-700 shadow-sm hover:bg-neutral-50 disabled:opacity-60" :disabled="refreshing" @click="refresh">
                            <RefreshCw class="h-4 w-4" :class="refreshing && 'animate-spin'" />
                            {{ refreshing ? 'Duke rifreskuar…' : 'Rifresko' }}
                        </button>
                        <Link href="/super-admin/tenants" class="inline-flex items-center gap-2 rounded-xl bg-[#17745c] px-4 py-2.5 text-sm font-semibold text-white no-underline shadow-sm hover:bg-[#125f4c]">
                            Menaxho hotelet <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </div>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article v-for="kpi in kpis" :key="kpi.label" class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm shadow-neutral-200/30">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-neutral-500">{{ kpi.label }}</p>
                            <p class="mt-3 text-3xl font-semibold tracking-tight text-neutral-950">{{ kpi.value }}</p>
                            <p class="mt-2 text-xs text-neutral-400">
                                <span v-if="kpi.lead !== undefined" class="font-semibold" :class="kpi.leadClass">{{ kpi.lead }}</span>
                                <span :class="kpi.lead !== undefined && 'ml-1'">{{ kpi.detail }}</span>
                            </p>
                        </div>
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl" :class="toneClass(kpi.tone)">
                            <component :is="kpi.icon" class="h-5 w-5" :stroke-width="1.8" />
                        </span>
                    </div>
                </article>
            </section>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(340px,0.75fr)]">
                <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm shadow-neutral-200/30">
                    <div class="flex items-center justify-between gap-3 border-b border-neutral-200 px-5 py-4">
                        <div>
                            <h2 class="text-lg font-semibold text-neutral-900">Prioritetet e sotme</h2>
                            <p class="mt-1 text-sm text-neutral-500">Çfarë kërkon ndërhyrjen e super-adminit.</p>
                        </div>
                        <span class="rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-semibold text-neutral-600">{{ needsAttention.length }} urgjente</span>
                    </div>

                    <div v-if="!needsAttention.length" class="m-5 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/70 p-4">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-700"><Check class="h-5 w-5" :stroke-width="2.4" /></span>
                        <div>
                            <p class="font-semibold text-emerald-800">Abonimet janë në rregull</p>
                            <p class="mt-1 text-xs text-emerald-700/70">Asnjë pagesë e vonuar ose hotel i pezulluar.</p>
                        </div>
                    </div>

                    <ul v-else class="divide-y divide-neutral-100 border-b border-neutral-100">
                        <li v-for="item in needsAttention" :key="item.id" class="flex flex-col gap-3 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="h-2 w-2 shrink-0 rounded-full" :class="item.severity === 'danger' ? 'bg-red-500' : 'bg-amber-500'" />
                                <Link :href="`/super-admin/tenants/${item.id}`" class="truncate text-sm font-semibold text-neutral-900 no-underline hover:text-emerald-700">{{ item.name }}</Link>
                            </div>
                            <div class="flex items-center gap-3 pl-5 sm:pl-0">
                                <span class="rounded-full border px-2 py-0.5 text-xs font-medium" :class="attentionClass(item.severity)">{{ item.reason }}</span>
                                <span v-if="item.date" class="text-xs text-neutral-400">{{ date(item.date) }}</span>
                            </div>
                        </li>
                    </ul>

                    <div class="grid gap-3 px-5 pb-5 sm:grid-cols-3" :class="needsAttention.length && 'pt-5'">
                        <div v-for="signal in signals" :key="signal.label" class="rounded-xl border border-neutral-200 p-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-neutral-500">{{ signal.label }}</span>
                                <span class="font-semibold" :class="signal.tone === 'ok' ? 'text-emerald-700' : 'text-amber-700'">{{ signal.value }}/{{ signal.total }}</span>
                            </div>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-neutral-100">
                                <div class="h-full rounded-full" :class="signal.tone === 'ok' ? 'bg-emerald-600' : 'bg-amber-500'" :style="{ width: `${ratio(signal.value, signal.total)}%` }" />
                            </div>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm shadow-neutral-200/30">
                    <div class="border-b border-neutral-200 px-5 py-4">
                        <h2 class="text-lg font-semibold text-neutral-900">Shëndeti i platformës</h2>
                        <p class="mt-1 text-sm text-neutral-500">Konfigurimi i tenant-ëve.</p>
                    </div>
                    <div class="flex items-center gap-5 px-5 py-5">
                        <div class="relative grid h-24 w-24 shrink-0 place-items-center rounded-full" :style="{ background: `conic-gradient(#17745c 0 ${stats.health_score}%, #edf1ef ${stats.health_score}% 100%)` }">
                            <span class="absolute inset-2 rounded-full bg-white" />
                            <span class="relative text-xl font-semibold text-neutral-900">{{ stats.health_score }}%</span>
                        </div>
                        <div>
                            <p class="font-semibold text-neutral-900">{{ healthLabel }}</p>
                            <p class="mt-1 text-xs leading-5 text-neutral-500">{{ healthDetail }}</p>
                        </div>
                    </div>
                    <div class="divide-y divide-neutral-100 border-t border-neutral-200 px-5">
                        <div class="flex items-center justify-between py-3 text-sm"><span class="text-neutral-600">Abonimet</span><span class="font-semibold" :class="stats.subscriptions_attention ? 'text-amber-700' : 'text-emerald-700'">{{ stats.subscriptions_attention ? `${stats.subscriptions_attention} kërkojnë vëmendje` : 'Në rregull' }}</span></div>
                        <div class="flex items-center justify-between py-3 text-sm"><span class="text-neutral-600">Domain primar</span><span class="font-semibold" :class="stats.domains_configured === stats.hotels_total ? 'text-emerald-700' : 'text-amber-700'">{{ stats.hotels_total - stats.domains_configured ? `${stats.hotels_total - stats.domains_configured} mungojnë` : 'Të plotë' }}</span></div>
                        <div class="flex items-center justify-between py-3 text-sm"><span class="text-neutral-600">Channex / POK</span><span class="font-semibold" :class="stats.integrations_ready === stats.integrations_total ? 'text-emerald-700' : 'text-amber-700'">{{ stats.integrations_ready === stats.integrations_total ? 'Të konfiguruara' : 'Kërkojnë konfigurim' }}</span></div>
                    </div>
                </section>
            </div>

            <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm shadow-neutral-200/30">
                <div class="flex items-center justify-between gap-3 border-b border-neutral-200 px-5 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-900">Hotelet e fundit</h2>
                        <p class="mt-1 text-sm text-neutral-500">Portofoli i shtuar së fundmi.</p>
                    </div>
                    <Link href="/super-admin/tenants" class="text-sm font-semibold text-emerald-700 no-underline">Shiko të gjitha <ArrowRight class="ml-1 inline h-4 w-4" /></Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-sm">
                        <thead><tr class="border-b border-neutral-200 bg-neutral-50/70 text-left text-[11px] uppercase tracking-wide text-neutral-400"><th class="px-5 py-3 font-semibold">Hoteli</th><th class="px-4 py-3 font-semibold">Abonimi</th><th class="px-4 py-3 font-semibold">Përdorimi</th><th class="px-5 py-3 text-right font-semibold">Statusi</th></tr></thead>
                        <tbody class="divide-y divide-neutral-100">
                            <tr v-for="tenant in recentTenants" :key="tenant.id" class="hover:bg-neutral-50/60">
                                <td class="px-5 py-3.5"><div class="flex items-center gap-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-xs font-bold text-emerald-700">{{ tenant.name.split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase() }}</span><div class="min-w-0"><Link :href="`/super-admin/tenants/${tenant.id}`" class="truncate font-semibold text-neutral-900 no-underline hover:text-emerald-700">{{ tenant.name }}</Link><p class="mt-0.5 truncate text-xs text-neutral-400">{{ tenant.domain || tenant.slug }} · {{ date(tenant.created_at) }}</p></div></div></td>
                                <td class="px-4 py-3.5"><p class="font-semibold text-neutral-900">{{ money(tenant.mrr_cents) }} / muaj</p><p class="mt-0.5 text-xs text-neutral-400">{{ tenant.billing_cycle === 'annual' ? 'Faturim vjetor' : 'Faturim mujor' }}</p></td>
                                <td class="px-4 py-3.5"><p class="text-neutral-700">{{ tenant.users_count }} përdorues</p><p class="mt-0.5 text-xs text-neutral-400">{{ tenant.modules.length }} module</p></td>
                                <td class="px-5 py-3.5 text-right"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(tenant.subscription_status)"><span class="h-1.5 w-1.5 rounded-full" :class="statusDotClass(tenant.subscription_status)" />{{ statusLabel(tenant.subscription_status) }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm shadow-neutral-200/30">
                <div class="flex items-center justify-between gap-3 border-b border-neutral-200 px-5 py-4">
                    <div><h2 class="text-lg font-semibold text-neutral-900">Adoptimi i moduleve</h2><p class="mt-1 text-sm text-neutral-500">Mbulimi aktual në portofol.</p></div>
                    <Gauge class="h-5 w-5 text-neutral-400" />
                </div>
                <div class="flex flex-wrap gap-2 p-5">
                    <span v-for="module in moduleAdoption" :key="module.code" class="rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-xs text-neutral-600"><strong class="mr-1 text-neutral-900">{{ module.hotels_count }}/{{ stats.hotels_total }}</strong>{{ module.name }}</span>
                </div>
            </section>
        </div>
    </SuperAdminLayout>
</template>
