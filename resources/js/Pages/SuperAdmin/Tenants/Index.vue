<script setup>
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Button from '@/Components/UI/Button.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Building2,
    Check,
    CircleAlert,
    CreditCard,
    Download,
    ExternalLink,
    Globe,
    Plug,
    Search,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    tenants: Array,
    currentTenantId: Number,
});

const form = useForm({
    name: '',
    slug: '',
    primary_domain: '',
    timezone: 'Europe/Tirane',
    currency: 'EUR',
    owner_name: '',
    owner_email: '',
});

const editingTenant = ref(null);
const billingForm = useForm({
    status: 'active',
    billing_cycle: 'monthly',
    current_period_ends_at: '',
    notes: '',
    modules: {},
});

const showCreate = ref(false);
const configTab = ref('domains');

function openCreate() {
    form.reset('name', 'slug', 'primary_domain', 'owner_name', 'owner_email');
    form.clearErrors();
    showCreate.value = true;
}

function closeCreate() {
    if (!form.processing) showCreate.value = false;
}

function createTenant() {
    form.post(route('super-admin.tenants.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name', 'slug', 'primary_domain', 'owner_name', 'owner_email');
            showCreate.value = false;
        },
    });
}

// ---- Konfigurimi (domains + integrimet) ----
const configTenant = ref(null);

const domainForm = useForm({ domain: '' });
const channexForm = useForm({ enabled: false, api_key: '', webhook_secret: '', property_id: '', base_url: '' });
const pokForm = useForm({ enabled: false, key_id: '', key_secret: '', merchant_id: '', production: false });

function openConfig(tenant, resetTab = true) {
    if (resetTab) configTab.value = 'domains';
    configTenant.value = tenant;
    domainForm.reset();
    domainForm.clearErrors();

    // Secrets are NEVER sent to the browser — the fields start blank and a
    // blank submit keeps whatever is stored on the server.
    channexForm.enabled = tenant.integrations.channex.enabled;
    channexForm.api_key = '';
    channexForm.webhook_secret = '';
    channexForm.property_id = tenant.integrations.channex.property_id || '';
    channexForm.base_url = tenant.integrations.channex.base_url || '';
    channexForm.clearErrors();

    pokForm.enabled = tenant.integrations.pok.enabled;
    pokForm.key_id = '';
    pokForm.key_secret = '';
    pokForm.merchant_id = tenant.integrations.pok.merchant_id || '';
    pokForm.production = tenant.integrations.pok.production;
    pokForm.clearErrors();
}

function closeConfig() {
    if (!domainForm.processing && !channexForm.processing && !pokForm.processing) configTenant.value = null;
}

function refreshConfig() {
    router.reload({
        only: ['tenants'],
        onSuccess: (page) => {
            const fresh = page.props.tenants.find((t) => t.id === configTenant.value?.id);
            if (fresh) openConfig(fresh, false);
        },
    });
}

function addDomain() {
    domainForm.post(route('super-admin.tenants.domains.store', configTenant.value.id), {
        preserveScroll: true,
        onSuccess: refreshConfig,
    });
}

function removeDomain(domain) {
    router.delete(route('super-admin.tenants.domains.destroy', [configTenant.value.id, domain.id]), {
        preserveScroll: true,
        onSuccess: refreshConfig,
    });
}

function makePrimary(domain) {
    router.patch(route('super-admin.tenants.domains.primary', [configTenant.value.id, domain.id]), {}, {
        preserveScroll: true,
        onSuccess: refreshConfig,
    });
}

function saveChannex() {
    channexForm.put(route('super-admin.tenants.integrations.update', [configTenant.value.id, 'channex']), {
        preserveScroll: true,
        onSuccess: refreshConfig,
    });
}

function savePok() {
    pokForm.put(route('super-admin.tenants.integrations.update', [configTenant.value.id, 'pok']), {
        preserveScroll: true,
        onSuccess: refreshConfig,
    });
}

function switchTenant(tenant) {
    router.post(route('super-admin.tenants.switch', tenant.id));
}

const search = ref('');
const statusFilter = ref('all');
const billingCycle = ref('');
const sortOrder = ref('mrr');
const selectedTenant = ref(null);

function moduleEnabled(tenant, code) {
    return Boolean(tenant.billing?.modules?.[code]?.enabled);
}

function monthlyMrr(tenant) {
    if (tenant.status !== 'active' || !['active', 'trialing'].includes(tenant.billing?.status)) return 0;
    return tenant.billing.billing_cycle === 'annual'
        ? Math.round(Number(tenant.billing.annual_cents || 0) / 12)
        : Number(tenant.billing.monthly_fixed_cents || 0);
}

function tenantHealth(tenant) {
    const status = hotelStatus(tenant);
    if (status.tone !== 'ok') return { ...status, detail: 'Kontrollo abonimin ose statusin' };

    const missing = [];
    if (!tenant.primary_domain) missing.push('domain');
    if (moduleEnabled(tenant, 'channel_manager')
        && (!tenant.integrations?.channex?.enabled || !tenant.integrations?.channex?.has_api_key)) missing.push('Channex');
    if (moduleEnabled(tenant, 'booking_engine')
        && (!tenant.integrations?.pok?.enabled || !tenant.integrations?.pok?.has_key_id)) missing.push('POK');

    return missing.length
        ? { label: 'Kërkon vëmendje', tone: 'attention', detail: `Mungon: ${missing.join(', ')}` }
        : { label: 'Në rregull', tone: 'ok', detail: 'Konfigurimi është i plotë' };
}

const summary = computed(() => ({
    active: props.tenants.filter((tenant) => tenant.status === 'active').length,
    users: props.tenants.reduce((total, tenant) => total + Number(tenant.users_count || 0), 0),
    mrr: props.tenants.reduce((total, tenant) => total + monthlyMrr(tenant), 0),
    attention: props.tenants.filter((tenant) => tenant.status !== 'suspended' && tenantHealth(tenant).tone !== 'ok').length,
    suspended: props.tenants.filter((tenant) => tenant.status === 'suspended').length,
}));

const statusChips = computed(() => [
    { key: 'all', label: 'Të gjithë', count: props.tenants.length },
    { key: 'healthy', label: 'Në rregull', count: props.tenants.filter((tenant) => tenantHealth(tenant).tone === 'ok').length },
    { key: 'attention', label: 'Kërkojnë vëmendje', count: summary.value.attention },
    { key: 'suspended', label: 'Të pezulluar', count: summary.value.suspended },
]);

const filteredTenants = computed(() => {
    const q = search.value.trim().toLowerCase();
    const rows = props.tenants.filter((tenant) => {
        const matchesSearch = !q || [tenant.name, tenant.slug, tenant.primary_domain, ...(tenant.domains || []).map((domain) => domain.domain)]
            .filter(Boolean)
            .some((value) => String(value).toLowerCase().includes(q));
        const health = tenantHealth(tenant);
        const matchesStatus = statusFilter.value === 'all'
            || (statusFilter.value === 'healthy' && health.tone === 'ok')
            || (statusFilter.value === 'attention' && tenant.status !== 'suspended' && health.tone !== 'ok')
            || (statusFilter.value === 'suspended' && tenant.status === 'suspended');

        return matchesSearch
            && matchesStatus
            && (!billingCycle.value || tenant.billing?.billing_cycle === billingCycle.value);
    });

    return [...rows].sort((left, right) => {
        if (sortOrder.value === 'name') return left.name.localeCompare(right.name, 'sq');
        if (sortOrder.value === 'attention') {
            return Number(tenantHealth(right).tone !== 'ok') - Number(tenantHealth(left).tone !== 'ok')
                || left.name.localeCompare(right.name, 'sq');
        }
        return monthlyMrr(right) - monthlyMrr(left) || left.name.localeCompare(right.name, 'sq');
    });
});

function toggleStatus(tenant) {
    const suspend = tenant.status === 'active';
    const msg = suspend
        ? `Të pezulloj ${tenant.name}? Hoteli s'do të hapet dot (faqja, paneli, rezervimet) derisa ta riaktivizosh.`
        : `Të riaktivizoj ${tenant.name}?`;
    if (!confirm(msg)) return;
    router.patch(route('super-admin.tenants.status', tenant.id), {
        status: suspend ? 'suspended' : 'active',
    }, { preserveScroll: true });
}

const openMenuId = ref(null);
function toggleMenu(id) { openMenuId.value = openMenuId.value === id ? null : id; }
function closeMenu() { openMenuId.value = null; }

function enabledCount(tenant) {
    return Object.values(tenant.billing.modules).filter((m) => m.enabled).length;
}

function initials(name) {
    return name.split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
}

function exportTenants() {
    const header = ['Hoteli', 'Domain', 'Abonimi', 'Përdorues', 'Module', 'MRR EUR', 'Shëndeti', 'Statusi'];
    const rows = filteredTenants.value.map((tenant) => [
        tenant.name,
        tenant.primary_domain || '',
        tenant.billing?.billing_cycle === 'annual' ? 'Vjetore' : 'Mujore',
        tenant.users_count,
        enabledCount(tenant),
        (monthlyMrr(tenant) / 100).toFixed(2),
        tenantHealth(tenant).label,
        hotelStatus(tenant).label,
    ]);
    const csv = [header, ...rows]
        .map((row) => row.map((value) => `"${String(value ?? '').replaceAll('"', '""')}"`).join(','))
        .join('\n');
    const link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob([`\uFEFF${csv}`], { type: 'text/csv;charset=utf-8' }));
    link.download = `lora-hotelet-${new Date().toISOString().slice(0, 10)}.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
}

function shortDate(value) {
    return new Intl.DateTimeFormat('sq-AL', { day: '2-digit', month: '2-digit' }).format(new Date(value));
}

// One status per hotel: suspension and billing trouble collapse into a single
// clear pill instead of two separate 'Aktiv' badges.
function hotelStatus(tenant) {
    if (tenant.status === 'suspended') return { label: 'Pezulluar', tone: 'bad' };
    const b = tenant.billing || {};
    if (b.status === 'past_due') return { label: 'Pagesë e vonuar', tone: 'warn' };
    if (['suspended', 'canceled', 'inactive'].includes(b.status)) return { label: statusLabel(b.status), tone: 'warn' };
    if (b.current_period_ends_at) {
        const ends = new Date(b.current_period_ends_at);
        const soon = new Date();
        soon.setDate(soon.getDate() + 14);
        if (ends <= soon) return { label: `Rinovim më ${shortDate(b.current_period_ends_at)}`, tone: 'warn' };
    }
    return { label: 'Aktiv', tone: 'ok' };
}

function statusPillClass(tone) {
    return { ok: 'bg-emerald-50 text-emerald-700', attention: 'bg-amber-50 text-amber-700', warn: 'bg-amber-50 text-amber-700', bad: 'bg-red-50 text-red-700' }[tone] || 'bg-neutral-100 text-neutral-600';
}
function statusDotClass(tone) {
    return { ok: 'bg-emerald-500', attention: 'bg-amber-500', warn: 'bg-amber-500', bad: 'bg-red-500' }[tone] || 'bg-neutral-400';
}

function openBilling(tenant) {
    editingTenant.value = tenant;
    billingForm.status = tenant.billing.status;
    billingForm.billing_cycle = tenant.billing.billing_cycle;
    billingForm.current_period_ends_at = tenant.billing.current_period_ends_at || '';
    billingForm.notes = tenant.billing.notes || '';
    billingForm.modules = Object.fromEntries(
        Object.entries(tenant.billing.modules).map(([code, module]) => [
            code,
            { enabled: module.enabled, quantity: module.quantity },
        ]),
    );
    billingForm.clearErrors();
}

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const tenantId = Number(params.get('manage'));
    const tenant = props.tenants.find((item) => item.id === tenantId);
    if (!tenant) return;

    if (params.get('section') === 'billing') openBilling(tenant);
    if (params.get('section') === 'config') openConfig(tenant);
});

function closeBilling() {
    if (!billingForm.processing) editingTenant.value = null;
}

function saveBilling() {
    billingForm.put(route('super-admin.tenants.subscription.update', editingTenant.value.id), {
        preserveScroll: true,
        onSuccess: closeBilling,
    });
}

function money(cents, currency = 'EUR') {
    return new Intl.NumberFormat('sq-AL', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format((cents || 0) / 100);
}

function statusLabel(status) {
    return {
        trialing: 'Provë',
        active: 'Aktiv',
        past_due: 'Pagesë e vonuar',
        suspended: 'Pezulluar',
        canceled: 'Anuluar',
        inactive: 'Joaktiv',
    }[status] || status;
}
</script>

<template>
    <Head :title="$t('admin.generated.k_0f144fdc6f3c')" />

    <SuperAdminLayout title="Hotelet & abonimet — Lora Control Panel">
        <div class="mx-auto max-w-[1480px] space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <PageHeader
                        title="Hotelet & abonimet"
                        :breadcrumbs="[{ label: 'Control Panel', href: '/super-admin' }, { label: 'Hotelet & abonimet' }]"
                    />
                    <p class="mt-1 text-sm text-neutral-500">Menaxho tenantët, të ardhurat dhe shëndetin e platformës.</p>
                </div>
                <Button variant="primary" @click="openCreate">+ Shto hotel</Button>
            </div>

            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm shadow-neutral-200/30">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-neutral-500">Hotele aktive</p>
                            <p class="mt-3 text-3xl font-semibold tracking-tight text-neutral-900">{{ summary.active }}</p>
                            <p class="mt-1 text-xs text-neutral-400">{{ summary.users }} përdorues në platformë</p>
                        </div>
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-50 text-emerald-700"><Building2 class="h-5 w-5" /></span>
                    </div>
                </article>
                <article class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm shadow-neutral-200/30">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-neutral-500">MRR i vlerësuar</p>
                            <p class="mt-3 text-3xl font-semibold tracking-tight text-neutral-900">{{ money(summary.mrr, 'EUR') }}</p>
                            <p class="mt-1 text-xs text-neutral-400">Nga abonimet aktive</p>
                        </div>
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-blue-50 text-blue-700"><CreditCard class="h-5 w-5" /></span>
                    </div>
                </article>
                <article class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm shadow-neutral-200/30">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-neutral-500">Kërkojnë vëmendje</p>
                            <p class="mt-3 text-3xl font-semibold tracking-tight" :class="summary.attention ? 'text-amber-700' : 'text-neutral-900'">{{ summary.attention }}</p>
                            <p class="mt-1 text-xs text-neutral-400">Abonime, domain-e ose integrime</p>
                        </div>
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-amber-50 text-amber-700"><CircleAlert class="h-5 w-5" /></span>
                    </div>
                </article>
            </section>

            <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm shadow-neutral-200/30">
                <div class="flex flex-col gap-4 border-b border-neutral-200 px-5 py-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-900">Portofoli i hoteleve</h2>
                        <p class="mt-1 text-sm text-neutral-500">Pamje e përmbledhur e përdorimit dhe konfigurimit.</p>
                    </div>
                    <Button v-if="tenants.length" variant="outline" class="gap-2" @click="exportTenants">
                        <Download class="h-4 w-4" /> Eksporto CSV
                    </Button>
                </div>

                <div v-if="tenants.length" class="space-y-4 border-b border-neutral-100 px-5 py-4">
                    <div class="grid gap-3 lg:grid-cols-[minmax(260px,1fr)_190px_190px]">
                        <label class="relative block">
                            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                            <input v-model="search" type="search" placeholder="Kërko hotel, slug ose domain…" class="w-full rounded-xl border-neutral-300 py-2.5 pl-10 pr-3 text-sm" />
                        </label>
                        <select v-model="billingCycle" class="rounded-xl border-neutral-300 py-2.5 text-sm text-neutral-700">
                            <option value="">Të gjitha pagesat</option>
                            <option value="monthly">Mujore</option>
                            <option value="annual">Vjetore</option>
                        </select>
                        <select v-model="sortOrder" class="rounded-xl border-neutral-300 py-2.5 text-sm text-neutral-700">
                            <option value="mrr">Rendit: MRR</option>
                            <option value="name">Rendit: Emri</option>
                            <option value="attention">Rendit: Vëmendja</option>
                        </select>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="chip in statusChips"
                            :key="chip.key"
                            type="button"
                            class="rounded-full border px-3 py-1.5 text-xs font-medium transition"
                            :class="statusFilter === chip.key ? 'border-[#123d32] bg-[#123d32] text-white' : 'border-neutral-200 bg-white text-neutral-600 hover:border-neutral-300 hover:bg-neutral-50'"
                            @click="statusFilter = chip.key"
                        >
                            {{ chip.label }} · {{ chip.count }}
                        </button>
                    </div>
                </div>

                <div v-if="filteredTenants.length" class="overflow-x-auto">
                    <table class="w-full min-w-[920px] text-sm">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-left text-[11px] uppercase tracking-[0.08em] text-neutral-400">
                                <th class="px-5 py-3 font-semibold">Hoteli</th>
                                <th class="px-4 py-3 font-semibold">Abonimi</th>
                                <th class="px-4 py-3 font-semibold">Përdorimi</th>
                                <th class="px-4 py-3 font-semibold">Shëndeti</th>
                                <th class="px-4 py-3 font-semibold">Statusi</th>
                                <th class="px-5 py-3 text-right font-semibold">Veprime</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            <tr v-for="tenant in filteredTenants" :key="tenant.id" class="cursor-pointer transition hover:bg-emerald-50/30" @click="selectedTenant = tenant">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#e8f3ef] text-xs font-bold text-[#24624f]">{{ initials(tenant.name) }}</span>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <Link :href="route('super-admin.tenants.show', tenant.id)" class="truncate font-semibold text-neutral-900 no-underline hover:text-emerald-700" @click.stop>{{ tenant.name }}</Link>
                                                <span v-if="tenant.id === currentTenantId" class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">AKTUAL</span>
                                            </div>
                                            <p class="mt-0.5 max-w-[280px] truncate text-xs text-neutral-400">{{ tenant.primary_domain || tenant.slug }} · {{ tenant.timezone }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-semibold tabular-nums text-neutral-900">{{ money(monthlyMrr(tenant), tenant.billing.currency) }} <span class="text-xs font-normal text-neutral-400">/ muaj</span></p>
                                    <p class="mt-0.5 text-xs text-neutral-500">{{ tenant.billing.billing_cycle === 'annual' ? 'Faturim vjetor' : 'Faturim mujor' }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-medium text-neutral-800"><Users class="mr-1 inline h-3.5 w-3.5 text-neutral-400" />{{ tenant.users_count }} përdorues</p>
                                    <p class="mt-0.5 text-xs text-neutral-500">{{ enabledCount(tenant) }} {{ enabledCount(tenant) === 1 ? 'modul aktiv' : 'module aktive' }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" :class="statusPillClass(tenantHealth(tenant).tone)">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="statusDotClass(tenantHealth(tenant).tone)" />
                                        {{ tenantHealth(tenant).label }}
                                    </span>
                                    <p class="mt-1 max-w-[210px] truncate text-[11px] text-neutral-400">{{ tenantHealth(tenant).detail }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" :class="statusPillClass(hotelStatus(tenant).tone)">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="statusDotClass(hotelStatus(tenant).tone)" />
                                        {{ hotelStatus(tenant).label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4" @click.stop>
                                    <div class="flex items-center justify-end gap-2">
                                        <Button
                                            size="sm"
                                            :variant="tenant.id === currentTenantId ? 'outline' : 'primary'"
                                            :disabled="tenant.id === currentTenantId || tenant.status !== 'active'"
                                            @click="switchTenant(tenant)"
                                        >
                                            {{ tenant.id === currentTenantId ? 'Aktual' : 'Hap' }}
                                        </Button>
                                        <div class="relative">
                                            <button type="button" class="grid h-8 w-8 place-items-center rounded-lg border border-neutral-200 text-lg leading-none text-neutral-500 hover:bg-neutral-50" aria-label="Veprime të tjera" @click.stop="toggleMenu(tenant.id)">⋯</button>
                                            <div v-if="openMenuId === tenant.id" class="absolute right-0 z-50 mt-1 w-44 overflow-hidden rounded-xl border border-neutral-200 bg-white py-1 text-left shadow-lg">
                                                <button type="button" class="block w-full px-4 py-2 text-left text-sm text-neutral-700 hover:bg-neutral-50" @click="closeMenu(); openBilling(tenant)">Abonimi</button>
                                                <button type="button" class="block w-full px-4 py-2 text-left text-sm text-neutral-700 hover:bg-neutral-50" @click="closeMenu(); openConfig(tenant)">Konfigurimi</button>
                                                <button type="button" class="block w-full px-4 py-2 text-left text-sm hover:bg-neutral-50" :class="tenant.status === 'active' ? 'text-red-600' : 'text-success-700'" @click="closeMenu(); toggleStatus(tenant)">{{ tenant.status === 'active' ? 'Pezullo' : 'Aktivizo' }}</button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else-if="tenants.length" class="px-5 py-14 text-center">
                    <p class="text-sm font-medium text-neutral-700">Nuk u gjet asnjë hotel</p>
                    <p class="mt-1 text-xs text-neutral-500">Ndrysho kërkimin ose filtrat e zgjedhur.</p>
                </div>
                <div v-else class="px-5 py-16 text-center">
                    <p class="text-sm font-medium text-neutral-700">Ende asnjë hotel</p>
                    <p class="mt-1 text-xs text-neutral-500">Krijo hotelin e parë të platformës.</p>
                    <Button variant="primary" class="mt-4" @click="openCreate">+ Shto hotel</Button>
                </div>

                <div v-if="tenants.length" class="border-t border-neutral-100 px-5 py-3 text-xs text-neutral-400">
                    Po shfaqen {{ filteredTenants.length }} nga {{ tenants.length }} hotele
                </div>
            </section>

            <div v-if="openMenuId" class="fixed inset-0 z-40" @click="closeMenu" />
        </div>

        <Teleport to="body">
            <div v-if="selectedTenant" class="fixed inset-0 z-50 bg-neutral-950/40" @click.self="selectedTenant = null">
                <aside class="ml-auto flex h-full w-full max-w-lg flex-col bg-white shadow-2xl">
                    <div class="flex items-start justify-between border-b border-neutral-200 px-6 py-5">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#e8f3ef] text-sm font-bold text-[#24624f]">{{ initials(selectedTenant.name) }}</span>
                            <div class="min-w-0">
                                <h2 class="truncate text-lg font-semibold text-neutral-900">{{ selectedTenant.name }}</h2>
                                <p class="truncate text-sm text-neutral-500">{{ selectedTenant.primary_domain || selectedTenant.slug }}</p>
                            </div>
                        </div>
                        <button type="button" class="rounded-xl p-2 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700" aria-label="Mbyll" @click="selectedTenant = null"><X class="h-5 w-5" /></button>
                    </div>

                    <div class="flex-1 space-y-6 overflow-y-auto p-6">
                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-xl bg-neutral-50 p-3">
                                <p class="text-[11px] font-medium uppercase tracking-wide text-neutral-400">MRR</p>
                                <p class="mt-1 font-semibold text-neutral-900">{{ money(monthlyMrr(selectedTenant), selectedTenant.billing.currency) }}</p>
                            </div>
                            <div class="rounded-xl bg-neutral-50 p-3">
                                <p class="text-[11px] font-medium uppercase tracking-wide text-neutral-400">Përdorues</p>
                                <p class="mt-1 font-semibold text-neutral-900">{{ selectedTenant.users_count }}</p>
                            </div>
                            <div class="rounded-xl bg-neutral-50 p-3">
                                <p class="text-[11px] font-medium uppercase tracking-wide text-neutral-400">Module</p>
                                <p class="mt-1 font-semibold text-neutral-900">{{ enabledCount(selectedTenant) }}</p>
                            </div>
                        </div>

                        <section>
                            <h3 class="text-sm font-semibold text-neutral-900">Kontrolli i shëndetit</h3>
                            <div class="mt-3 divide-y divide-neutral-100 rounded-xl border border-neutral-200">
                                <div class="flex items-center justify-between gap-3 px-4 py-3">
                                    <span class="text-sm text-neutral-600">Abonimi</span>
                                    <span class="text-sm font-medium text-neutral-900">{{ statusLabel(selectedTenant.billing.status) }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3 px-4 py-3">
                                    <span class="text-sm text-neutral-600">Domain primar</span>
                                    <span class="max-w-[240px] truncate text-sm font-medium" :class="selectedTenant.primary_domain ? 'text-neutral-900' : 'text-amber-700'">{{ selectedTenant.primary_domain || 'Mungon' }}</span>
                                </div>
                                <div v-if="moduleEnabled(selectedTenant, 'channel_manager')" class="flex items-center justify-between gap-3 px-4 py-3">
                                    <span class="text-sm text-neutral-600">Channex</span>
                                    <span class="text-sm font-medium" :class="selectedTenant.integrations.channex.enabled && selectedTenant.integrations.channex.has_api_key ? 'text-emerald-700' : 'text-amber-700'">{{ selectedTenant.integrations.channex.enabled && selectedTenant.integrations.channex.has_api_key ? 'Konfiguruar' : 'Kërkon konfigurim' }}</span>
                                </div>
                                <div v-if="moduleEnabled(selectedTenant, 'booking_engine')" class="flex items-center justify-between gap-3 px-4 py-3">
                                    <span class="text-sm text-neutral-600">POK</span>
                                    <span class="text-sm font-medium" :class="selectedTenant.integrations.pok.enabled && selectedTenant.integrations.pok.has_key_id ? 'text-emerald-700' : 'text-amber-700'">{{ selectedTenant.integrations.pok.enabled && selectedTenant.integrations.pok.has_key_id ? 'Konfiguruar' : 'Kërkon konfigurim' }}</span>
                                </div>
                            </div>
                        </section>

                        <section>
                            <h3 class="text-sm font-semibold text-neutral-900">Modulet aktive</h3>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span v-for="module in Object.values(selectedTenant.billing.modules).filter((item) => item.enabled)" :key="module.code" class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700">{{ module.name }}</span>
                            </div>
                        </section>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 border-t border-neutral-200 px-6 py-4">
                        <Button variant="outline" @click="selectedTenant = null">Mbyll</Button>
                        <Button variant="outline" @click="openConfig(selectedTenant); selectedTenant = null">Konfigurimi</Button>
                        <Link :href="route('super-admin.tenants.show', selectedTenant.id)" class="inline-flex items-center gap-2 rounded-lg bg-[#123d32] px-4 py-2 text-sm font-medium text-white no-underline hover:bg-[#0d3027]">
                            Shiko profilin <ExternalLink class="h-4 w-4" />
                        </Link>
                    </div>
                </aside>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="showCreate" class="fixed inset-0 z-50 flex items-end justify-center bg-neutral-950/50 p-0 sm:items-center sm:p-6" @click.self="closeCreate">
                <section class="max-h-[94vh] w-full max-w-lg overflow-y-auto rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                    <div class="sticky top-0 z-10 flex items-start justify-between border-b border-neutral-200 bg-white px-5 py-4 sm:px-6">
                        <div>
                            <h2 class="text-lg font-semibold text-neutral-900">Krijo hotel të ri</h2>
                            <p class="mt-1 text-sm text-neutral-500">Krijon tenantin dhe të lidh ty si owner.</p>
                        </div>
                        <button class="rounded-lg p-2 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700" type="button" @click="closeCreate">✕</button>
                    </div>
                    <form class="space-y-4 p-5 sm:p-6" @submit.prevent="createTenant">
                        <label class="block text-sm font-medium text-neutral-700">
{{ $t('admin.generated.k_576b58fc705e') }} <input v-model="form.name" required class="mt-1 w-full rounded-lg border-neutral-300 text-sm" :placeholder="$t('admin.generated.k_e47fcc86e582')" />
                            <span v-if="form.errors.name" class="mt-1 block text-xs text-danger-600">{{ form.errors.name }}</span>
                        </label>

                        <label class="block text-sm font-medium text-neutral-700">
{{ $t('admin.generated.k_e8913516cd11') }} <input v-model="form.slug" required class="mt-1 w-full rounded-lg border-neutral-300 text-sm" :placeholder="$t('admin.generated.k_05d2f8add0d6')" />
                            <span v-if="form.errors.slug" class="mt-1 block text-xs text-danger-600">{{ form.errors.slug }}</span>
                        </label>

                        <label class="block text-sm font-medium text-neutral-700">
{{ $t('admin.generated.k_1c5756d1cdd0') }} <input v-model="form.primary_domain" class="mt-1 w-full rounded-lg border-neutral-300 text-sm" :placeholder="$t('admin.generated.k_20a434fbd3d4')" />
                            <span v-if="form.errors.primary_domain" class="mt-1 block text-xs text-danger-600">{{ form.errors.primary_domain }}</span>
                        </label>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-sm font-medium text-neutral-700">
{{ $t('admin.generated.k_209467b9eba8') }} <input v-model="form.timezone" required class="mt-1 w-full rounded-lg border-neutral-300 text-sm" />
                            </label>
                            <label class="block text-sm font-medium text-neutral-700">
{{ $t('admin.generated.k_265099595021') }} <input v-model="form.currency" required maxlength="3" class="mt-1 w-full rounded-lg border-neutral-300 text-sm uppercase" />
                            </label>
                        </div>

                        <div class="rounded-lg border border-neutral-200 bg-neutral-50/60 p-3">
                            <p class="text-sm font-semibold text-neutral-800">Pronari i parë (opsional)</p>
                            <p class="mt-0.5 text-xs text-neutral-500">Merr rolin admin + owner; fjalëkalimin e vendos vetë me "Kam harruar fjalëkalimin".</p>

                            <label class="mt-3 block text-sm font-medium text-neutral-700">
                                Emri i pronarit
                                <input v-model="form.owner_name" class="mt-1 w-full rounded-lg border-neutral-300 text-sm" placeholder="Ana Berisha" />
                                <span v-if="form.errors.owner_name" class="mt-1 block text-xs text-danger-600">{{ form.errors.owner_name }}</span>
                            </label>

                            <label class="mt-3 block text-sm font-medium text-neutral-700">
                                Email i pronarit
                                <input v-model="form.owner_email" type="email" class="mt-1 w-full rounded-lg border-neutral-300 text-sm" placeholder="ana@hotelriviera.com" />
                                <span v-if="form.errors.owner_email" class="mt-1 block text-xs text-danger-600">{{ form.errors.owner_email }}</span>
                            </label>
                        </div>

                        <Button type="submit" class="w-full justify-center" :disabled="form.processing">
                            {{ form.processing ? $t('admin.generated.k_f0c3ff038037') : $t('admin.generated.k_da7ad2a15d4e') }}
                        </Button>
                    </form>
                </section>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="editingTenant" class="fixed inset-0 z-50 flex items-end justify-center bg-neutral-950/50 p-0 sm:items-center sm:p-6" @click.self="closeBilling">
                <section role="dialog" aria-modal="true" class="flex max-h-[94vh] w-full max-w-5xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                    <div class="flex items-start justify-between border-b border-neutral-200 bg-white px-5 py-4 sm:px-6">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#e8f3ef] text-xs font-bold text-[#24624f]">{{ initials(editingTenant.name) }}</span>
                            <div class="min-w-0">
                                <h2 class="truncate text-lg font-semibold text-neutral-900">Menaxho abonimin</h2>
                                <p class="truncate text-sm text-neutral-500">{{ editingTenant.name }} · {{ editingTenant.primary_domain || editingTenant.slug }}</p>
                            </div>
                        </div>
                        <button class="rounded-xl p-2 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700" type="button" aria-label="Mbyll abonimin" @click="closeBilling"><X class="h-5 w-5" /></button>
                    </div>

                    <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="saveBilling">
                        <div class="min-h-0 flex-1 overflow-y-auto">
                            <div class="grid lg:grid-cols-[280px_minmax(0,1fr)]">
                                <aside class="space-y-5 border-b border-neutral-200 bg-neutral-50/70 p-5 lg:border-b-0 lg:border-r sm:p-6">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-neutral-400">Detajet e planit</p>
                                        <div class="mt-3 rounded-xl border border-neutral-200 bg-white p-4">
                                            <p class="text-xs text-neutral-500">MRR aktual</p>
                                            <p class="mt-1 text-2xl font-semibold tracking-tight text-neutral-900">{{ money(monthlyMrr(editingTenant), editingTenant.billing.currency) }}</p>
                                            <p class="mt-1 text-xs text-neutral-400">{{ enabledCount(editingTenant) }} module aktive</p>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <label class="block text-sm font-medium text-neutral-700">
                                            Statusi i abonimit
                                            <select v-model="billingForm.status" class="mt-1.5 w-full rounded-xl border-neutral-300 py-2.5 text-sm">
                                                <option value="trialing">Provë</option>
                                                <option value="active">Aktiv</option>
                                                <option value="past_due">Pagesë e vonuar</option>
                                                <option value="suspended">Pezulluar</option>
                                                <option value="canceled">Anuluar</option>
                                            </select>
                                        </label>
                                        <label class="block text-sm font-medium text-neutral-700">
                                            Cikli i faturimit
                                            <select v-model="billingForm.billing_cycle" class="mt-1.5 w-full rounded-xl border-neutral-300 py-2.5 text-sm">
                                                <option value="monthly">Mujore</option>
                                                <option value="annual">Vjetore · -20%</option>
                                            </select>
                                        </label>
                                        <label class="block text-sm font-medium text-neutral-700">
                                            Data e rinovimit
                                            <input v-model="billingForm.current_period_ends_at" type="date" class="mt-1.5 w-full rounded-xl border-neutral-300 py-2.5 text-sm" />
                                        </label>
                                    </div>
                                </aside>

                                <div class="space-y-5 p-5 sm:p-6">
                                    <div>
                                        <h3 class="font-semibold text-neutral-900">Modulet e përfshira</h3>
                                        <p class="mt-1 text-sm text-neutral-500">Aktivizo vetëm modulet e kontraktuara. Lora Core mbetet gjithmonë aktiv.</p>
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <article
                                            v-for="module in Object.values(editingTenant.billing.modules)"
                                            :key="module.code"
                                            class="rounded-xl border p-4 transition"
                                            :class="billingForm.modules[module.code]?.enabled ? 'border-emerald-200 bg-emerald-50/50' : 'border-neutral-200 bg-white'"
                                        >
                                            <label class="flex min-w-0 cursor-pointer items-start gap-3" :class="module.locked && 'cursor-default'">
                                                <input
                                                    v-model="billingForm.modules[module.code].enabled"
                                                    type="checkbox"
                                                    class="mt-0.5 rounded border-neutral-300 text-emerald-600 focus:ring-emerald-500"
                                                    :disabled="module.locked"
                                                />
                                                <span class="min-w-0">
                                                    <span class="block text-sm font-semibold text-neutral-900">{{ module.name }}</span>
                                                    <span class="mt-1 block text-xs leading-5 text-neutral-500">{{ module.description }}</span>
                                                    <span v-if="module.billing_model === 'percentage'" class="mt-1 block text-xs font-medium text-emerald-700">{{ module.percentage_bps / 100 }}% për rezervim direkt</span>
                                                </span>
                                            </label>

                                            <label v-if="['tiered_per_room', 'per_user', 'per_pos'].includes(module.billing_model)" class="mt-3 flex items-center justify-between border-t border-neutral-200/70 pt-3 text-xs font-medium text-neutral-600">
                                                <span class="capitalize">{{ module.unit_label }}</span>
                                                <input
                                                    v-model.number="billingForm.modules[module.code].quantity"
                                                    type="number"
                                                    min="1"
                                                    max="10000"
                                                    class="w-24 rounded-lg border-neutral-300 py-1.5 text-right text-sm"
                                                    :disabled="!billingForm.modules[module.code].enabled"
                                                />
                                            </label>
                                        </article>
                                    </div>

                                    <label class="block text-sm font-medium text-neutral-700">
                                        Shënime të brendshme
                                        <textarea v-model="billingForm.notes" rows="2" class="mt-1.5 w-full rounded-xl border-neutral-300 text-sm" placeholder="Kontrata, marrëveshja ose shënime për pagesën…" />
                                    </label>

                                    <p v-if="Object.keys(billingForm.errors).length" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700">
                                        Kontrollo fushat e abonimit dhe provo përsëri.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center justify-between gap-3 border-t border-neutral-200 bg-white px-5 py-4 sm:px-6">
                            <p class="hidden text-xs text-neutral-400 sm:block">Ndryshimet aplikohen vetëm pasi të ruhen.</p>
                            <div class="flex items-center gap-3">
                                <Button type="button" variant="outline" @click="closeBilling">Anulo</Button>
                                <Button type="submit" :disabled="billingForm.processing">
                                    {{ billingForm.processing ? 'Duke ruajtur…' : 'Ruaj abonimin' }}
                                </Button>
                            </div>
                        </div>
                    </form>
                </section>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="configTenant" class="fixed inset-0 z-50 flex items-end justify-center bg-neutral-950/50 p-0 sm:items-center sm:p-6" @click.self="closeConfig">
                <section role="dialog" aria-modal="true" class="flex max-h-[94vh] w-full max-w-3xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                    <div class="flex items-start justify-between border-b border-neutral-200 bg-white px-5 py-4 sm:px-6">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#e8f3ef] text-xs font-bold text-[#24624f]">{{ initials(configTenant.name) }}</span>
                            <div class="min-w-0">
                                <h2 class="truncate text-lg font-semibold text-neutral-900">Konfigurimi i hotelit</h2>
                                <p class="truncate text-sm text-neutral-500">{{ configTenant.name }} · Domain-et dhe integrimet</p>
                            </div>
                        </div>
                        <button class="rounded-xl p-2 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700" type="button" aria-label="Mbyll konfigurimin" @click="closeConfig"><X class="h-5 w-5" /></button>
                    </div>

                    <div class="grid grid-cols-3 border-b border-neutral-200 bg-neutral-50/70 px-3 pt-2 sm:px-6">
                        <button type="button" class="flex items-center justify-center gap-2 border-b-2 px-3 py-3 text-sm font-medium transition" :class="configTab === 'domains' ? 'border-[#24624f] text-[#24624f]' : 'border-transparent text-neutral-500 hover:text-neutral-800'" @click="configTab = 'domains'">
                            <Globe class="h-4 w-4" /> Domain-et
                            <span class="rounded-full bg-neutral-200/70 px-1.5 py-0.5 text-[10px]">{{ configTenant.domains.length }}</span>
                        </button>
                        <button type="button" class="flex items-center justify-center gap-2 border-b-2 px-3 py-3 text-sm font-medium transition" :class="configTab === 'channex' ? 'border-[#24624f] text-[#24624f]' : 'border-transparent text-neutral-500 hover:text-neutral-800'" @click="configTab = 'channex'">
                            <Plug class="h-4 w-4" /> Channex
                            <span class="h-2 w-2 rounded-full" :class="configTenant.integrations.channex.enabled && configTenant.integrations.channex.has_api_key ? 'bg-emerald-500' : 'bg-neutral-300'" />
                        </button>
                        <button type="button" class="flex items-center justify-center gap-2 border-b-2 px-3 py-3 text-sm font-medium transition" :class="configTab === 'pok' ? 'border-[#24624f] text-[#24624f]' : 'border-transparent text-neutral-500 hover:text-neutral-800'" @click="configTab = 'pok'">
                            <CreditCard class="h-4 w-4" /> POK
                            <span class="h-2 w-2 rounded-full" :class="configTenant.integrations.pok.enabled && configTenant.integrations.pok.has_key_id ? 'bg-emerald-500' : 'bg-neutral-300'" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto p-5 sm:p-6">
                        <section v-if="configTab === 'domains'" class="space-y-5">
                            <div>
                                <h3 class="font-semibold text-neutral-900">Adresat e hotelit</h3>
                                <p class="mt-1 text-sm text-neutral-500">Menaxho domain-et ku përdoruesit mund të hapin këtë hotel.</p>
                            </div>

                            <ul class="divide-y divide-neutral-100 overflow-hidden rounded-xl border border-neutral-200">
                                <li v-for="domain in configTenant.domains" :key="domain.id" class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-neutral-50 text-neutral-400"><Globe class="h-4 w-4" /></span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-neutral-900">{{ domain.domain }}</p>
                                            <p class="mt-0.5 text-xs text-neutral-400">{{ domain.is_primary ? 'Domain primar' : 'Domain alternativ' }}</p>
                                        </div>
                                        <span v-if="domain.is_primary" class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Primar</span>
                                    </div>
                                    <div v-if="!domain.is_primary" class="flex shrink-0 gap-2 pl-12 sm:pl-0">
                                        <Button size="sm" variant="outline" @click="makePrimary(domain)">Bëje primar</Button>
                                        <Button size="sm" variant="outline" class="text-red-600" @click="removeDomain(domain)">Hiq</Button>
                                    </div>
                                </li>
                                <li v-if="!configTenant.domains.length" class="px-5 py-10 text-center">
                                    <span class="mx-auto grid h-11 w-11 place-items-center rounded-xl bg-neutral-50 text-neutral-400"><Globe class="h-5 w-5" /></span>
                                    <p class="mt-3 text-sm font-medium text-neutral-700">Ende pa domain</p>
                                    <p class="mt-1 text-xs text-neutral-500">Shto adresën e parë për këtë hotel.</p>
                                </li>
                            </ul>

                            <form class="rounded-xl border border-neutral-200 bg-neutral-50/60 p-4" @submit.prevent="addDomain">
                                <label class="block text-sm font-medium text-neutral-700">
                                    Domain i ri
                                    <div class="mt-1.5 flex flex-col gap-2 sm:flex-row">
                                        <input v-model="domainForm.domain" required class="w-full rounded-xl border-neutral-300 text-sm" placeholder="riviera.lorapms.com" />
                                        <Button type="submit" class="shrink-0" :disabled="domainForm.processing">{{ domainForm.processing ? 'Duke shtuar…' : 'Shto domain' }}</Button>
                                    </div>
                                </label>
                                <span v-if="domainForm.errors.domain" class="mt-1 block text-xs text-danger-600">{{ domainForm.errors.domain }}</span>
                            </form>
                        </section>

                        <form v-else-if="configTab === 'channex'" class="space-y-5" @submit.prevent="saveChannex">
                            <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 bg-neutral-50/60 p-4 sm:flex-row sm:items-center">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white text-neutral-500 shadow-sm"><Plug class="h-5 w-5" /></span>
                                <div class="mr-auto">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-semibold text-neutral-900">Channex Channel Manager</h3>
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium" :class="configTenant.integrations.channex.has_api_key ? 'bg-emerald-50 text-emerald-700' : 'bg-neutral-200/70 text-neutral-500'">
                                            <Check v-if="configTenant.integrations.channex.has_api_key" class="h-3 w-3" />{{ configTenant.integrations.channex.has_api_key ? 'Kredencialet e ruajtura' : 'Pa kredenciale' }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-neutral-500">Sinkronizon inventarin dhe rezervimet me OTA-t.</p>
                                </div>
                                <label class="flex shrink-0 cursor-pointer items-center gap-3 text-sm font-medium text-neutral-700">
                                    Aktiv
                                    <input v-model="channexForm.enabled" type="checkbox" class="peer sr-only" />
                                    <span class="relative h-6 w-11 rounded-full bg-neutral-300 transition peer-checked:bg-emerald-600 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5" />
                                </label>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="text-sm font-medium text-neutral-700">API key
                                    <input v-model="channexForm.api_key" type="password" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border-neutral-300 text-sm" :placeholder="configTenant.integrations.channex.has_api_key ? '•••• (lëre bosh për ta mbajtur)' : 'Ngjit çelësin Channex'" />
                                </label>
                                <label class="text-sm font-medium text-neutral-700">Webhook secret
                                    <input v-model="channexForm.webhook_secret" type="password" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border-neutral-300 text-sm" :placeholder="configTenant.integrations.channex.has_webhook_secret ? '•••• (lëre bosh për ta mbajtur)' : 'Ngjit webhook secret'" />
                                </label>
                                <label class="text-sm font-medium text-neutral-700">Property ID
                                    <input v-model="channexForm.property_id" class="mt-1.5 w-full rounded-xl border-neutral-300 text-sm" placeholder="p.sh. 5f2a…" />
                                    <span v-if="channexForm.errors.property_id" class="mt-1 block text-xs text-danger-600">{{ channexForm.errors.property_id }}</span>
                                </label>
                                <label class="text-sm font-medium text-neutral-700">Base URL <span class="font-normal text-neutral-400">(opsional)</span>
                                    <input v-model="channexForm.base_url" class="mt-1.5 w-full rounded-xl border-neutral-300 text-sm" placeholder="https://app.channex.io/api/v1" />
                                    <span v-if="channexForm.errors.base_url" class="mt-1 block text-xs text-danger-600">{{ channexForm.errors.base_url }}</span>
                                </label>
                            </div>

                            <div class="rounded-xl bg-blue-50 px-4 py-3 text-xs leading-5 text-blue-700">Fushat sekrete nuk rishfaqen. Lëri bosh nëse dëshiron të mbash vlerat aktuale.</div>
                            <div class="flex justify-end border-t border-neutral-200 pt-4">
                                <Button type="submit" :disabled="channexForm.processing">{{ channexForm.processing ? 'Duke ruajtur…' : 'Ruaj Channex' }}</Button>
                            </div>
                        </form>

                        <form v-else class="space-y-5" @submit.prevent="savePok">
                            <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 bg-neutral-50/60 p-4 sm:flex-row sm:items-center">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white text-neutral-500 shadow-sm"><CreditCard class="h-5 w-5" /></span>
                                <div class="mr-auto">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-semibold text-neutral-900">POK Payments</h3>
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium" :class="configTenant.integrations.pok.has_key_id ? 'bg-emerald-50 text-emerald-700' : 'bg-neutral-200/70 text-neutral-500'">
                                            <Check v-if="configTenant.integrations.pok.has_key_id" class="h-3 w-3" />{{ configTenant.integrations.pok.has_key_id ? 'Kredencialet e ruajtura' : 'Pa kredenciale' }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-neutral-500">Proceson pagesat me kartë në booking-un online.</p>
                                </div>
                                <label class="flex shrink-0 cursor-pointer items-center gap-3 text-sm font-medium text-neutral-700">
                                    Aktiv
                                    <input v-model="pokForm.enabled" type="checkbox" class="peer sr-only" />
                                    <span class="relative h-6 w-11 rounded-full bg-neutral-300 transition peer-checked:bg-emerald-600 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5" />
                                </label>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="text-sm font-medium text-neutral-700">Key ID
                                    <input v-model="pokForm.key_id" type="password" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border-neutral-300 text-sm" :placeholder="configTenant.integrations.pok.has_key_id ? '•••• (lëre bosh për ta mbajtur)' : 'Ngjit Key ID'" />
                                </label>
                                <label class="text-sm font-medium text-neutral-700">Key secret
                                    <input v-model="pokForm.key_secret" type="password" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border-neutral-300 text-sm" :placeholder="configTenant.integrations.pok.has_key_secret ? '•••• (lëre bosh për ta mbajtur)' : 'Ngjit Key secret'" />
                                </label>
                                <label class="text-sm font-medium text-neutral-700">Merchant ID
                                    <input v-model="pokForm.merchant_id" class="mt-1.5 w-full rounded-xl border-neutral-300 text-sm" placeholder="Merchant ID" />
                                    <span v-if="pokForm.errors.merchant_id" class="mt-1 block text-xs text-danger-600">{{ pokForm.errors.merchant_id }}</span>
                                </label>
                                <label class="flex items-center gap-3 rounded-xl border border-neutral-200 px-4 py-3 text-sm font-medium text-neutral-700 sm:mt-6">
                                    <input v-model="pokForm.production" type="checkbox" class="rounded border-neutral-300 text-emerald-600 focus:ring-emerald-500" />
                                    <span><span class="block">Mjedisi Production</span><span class="mt-0.5 block text-xs font-normal text-neutral-400">Aktivizo vetëm për pagesa reale.</span></span>
                                </label>
                            </div>

                            <div class="rounded-xl bg-blue-50 px-4 py-3 text-xs leading-5 text-blue-700">Fushat sekrete nuk rishfaqen. Lëri bosh nëse dëshiron të mbash vlerat aktuale.</div>
                            <div class="flex justify-end border-t border-neutral-200 pt-4">
                                <Button type="submit" :disabled="pokForm.processing">{{ pokForm.processing ? 'Duke ruajtur…' : 'Ruaj POK' }}</Button>
                            </div>
                        </form>
                    </div>

                    <div class="flex shrink-0 items-center justify-between border-t border-neutral-200 bg-neutral-50/60 px-5 py-3 sm:px-6">
                        <p class="text-xs text-neutral-400">Sekretet ruhen të enkriptuara.</p>
                        <Button type="button" variant="outline" @click="closeConfig">Mbyll</Button>
                    </div>
                </section>
            </div>
        </Teleport>
    </SuperAdminLayout>
</template>
