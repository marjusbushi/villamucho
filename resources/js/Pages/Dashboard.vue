<script setup>
import { ref, computed } from 'vue';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import ToastContainer from '@/Components/UI/ToastContainer.vue';
import { channelMeta } from '@/channels';
import {
    BedDouble, Wallet, CalendarDays, AlertTriangle, Coins, TrendingUp, TrendingDown,
    LogIn, LogOut, Sparkles, UtensilsCrossed, ArrowRight,
} from 'lucide-vue-next';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    arrivals: { type: Array, default: () => [] },
    departures: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
    roomStatusCounts: { type: Object, default: () => ({}) },
    housekeeping: { type: Array, default: () => [] },
    openPos: { type: Array, default: () => [] },
    alerts: { type: Array, default: () => [] },
    charts: { type: Object, default: () => ({ revenue14: [], occupancy14: [], channelMix: [] }) },
    currency: { type: String, default: '€' },
});

const user = usePage().props.auth.user;
const toasts = ref(null);
const perms = usePage().props.auth.user?.permissions || [];
const canUpdate = perms.includes('update_reservations');
const canPos = perms.includes('view_pos_orders');
const canHousekeeping = perms.includes('view_housekeeping');

const money = (v) => `${props.currency}${Number(v || 0).toLocaleString('sq-AL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const roomStatusMeta = {
    available: { label: 'Të lira', dot: 'bg-success-500', tile: 'bg-success-50 text-success-700 ring-success-200' },
    occupied: { label: 'Zëna', dot: 'bg-info-500', tile: 'bg-info-50 text-info-700 ring-info-200' },
    cleaning: { label: 'Pastrim', dot: 'bg-warning-500', tile: 'bg-warning-50 text-warning-700 ring-warning-200' },
    maintenance: { label: 'Mirëmbajtje', dot: 'bg-error-500', tile: 'bg-error-50 text-error-700 ring-error-200' },
};
const alertLevel = { error: 'bg-error-50 text-error-700 ring-error-200/60', warning: 'bg-warning-50 text-warning-700 ring-warning-200/60' };

const dayLabel = (d) => new Date(d).toLocaleDateString('sq-AL', { day: '2-digit', month: '2-digit' });
const weekday = (d) => new Date(d).toLocaleDateString('sq-AL', { weekday: 'short' });

// ---- chart scales ----
const rev14Max = computed(() => Math.max(1, ...(props.charts?.revenue14 || []).map((d) => d.room + d.pos)));
const channelMax = computed(() => Math.max(1, ...(props.charts?.channelMix || []).map((c) => c.revenue)));

function doCheckIn(r) {
    router.post(route('reservations.check-in', r.id), {}, {
        preserveScroll: true,
        onSuccess: () => toasts.value?.success(`Check-in: ${r.guest}`),
        onError: () => toasts.value?.error('Check-in dështoi.'),
    });
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <PageHeader title="Paneli" />

        <p class="mt-4 text-body-sm text-neutral-500">Mirëseerdhe, <span class="font-medium text-primary-900">{{ user.name }}</span> · {{ new Date().toLocaleDateString('sq-AL', { weekday: 'long', day: 'numeric', month: 'long' }) }}</p>

        <!-- ============ HERO KPIs ============ -->
        <div class="mt-4 grid grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Occupancy -->
            <Card>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-tiny text-neutral-500 uppercase tracking-wider">Mbushja sot</p>
                        <p class="text-h2 text-primary-900 mt-1 leading-none">{{ stats.occupancy }}%</p>
                        <p class="text-tiny text-neutral-400 mt-1">{{ stats.occupied }}/{{ stats.total_rooms }} dhoma</p>
                    </div>
                    <BedDouble class="h-5 w-5 text-ionian shrink-0" :stroke-width="1.75" />
                </div>
            </Card>
            <!-- Revenue today -->
            <Card>
                <div class="flex items-start justify-between">
                    <div class="min-w-0">
                        <p class="text-tiny text-neutral-500 uppercase tracking-wider">Të ardhura sot</p>
                        <p class="text-h2 text-primary-900 mt-1 leading-none truncate">{{ money(stats.revenue_today) }}</p>
                        <p class="text-tiny text-neutral-400 mt-1">Dhoma {{ money(stats.revenue_today_room) }} · Bar {{ money(stats.revenue_today_pos) }}</p>
                    </div>
                    <Coins class="h-5 w-5 text-brass shrink-0" :stroke-width="1.75" />
                </div>
            </Card>
            <!-- Revenue MTD -->
            <Card>
                <div class="flex items-start justify-between">
                    <div class="min-w-0">
                        <p class="text-tiny text-neutral-500 uppercase tracking-wider">Këtë muaj</p>
                        <p class="text-h2 text-primary-900 mt-1 leading-none truncate">{{ money(stats.revenue_month) }}</p>
                        <p v-if="stats.revenue_month_delta !== null" class="text-tiny mt-1 inline-flex items-center gap-1"
                           :class="stats.revenue_month_delta >= 0 ? 'text-success-600' : 'text-error-600'">
                            <component :is="stats.revenue_month_delta >= 0 ? TrendingUp : TrendingDown" class="h-3.5 w-3.5" />
                            {{ Math.abs(stats.revenue_month_delta) }}% vs muaji kaluar
                        </p>
                        <p v-else class="text-tiny text-neutral-400 mt-1">vs muaji kaluar: —</p>
                    </div>
                    <CalendarDays class="h-5 w-5 text-ionian shrink-0" :stroke-width="1.75" />
                </div>
            </Card>
            <!-- Outstanding -->
            <Card>
                <div class="flex items-start justify-between">
                    <div class="min-w-0">
                        <p class="text-tiny text-neutral-500 uppercase tracking-wider">Pa paguar</p>
                        <p class="text-h2 mt-1 leading-none truncate" :class="stats.outstanding > 0 ? 'text-error-600' : 'text-success-600'">{{ money(stats.outstanding) }}</p>
                        <p class="text-tiny text-neutral-400 mt-1">{{ stats.owing_count }} qëndrim(e) me borxh</p>
                    </div>
                    <Wallet class="h-5 w-5 text-driftwood shrink-0" :stroke-width="1.75" />
                </div>
            </Card>
            <!-- Cash & VAT -->
            <Card>
                <div class="flex items-start justify-between">
                    <div class="min-w-0">
                        <p class="text-tiny text-neutral-500 uppercase tracking-wider">Arka sot</p>
                        <p class="text-h2 text-primary-900 mt-1 leading-none truncate">{{ money(stats.cash_today) }}</p>
                        <p class="text-tiny text-neutral-400 mt-1">Kartë {{ money(stats.card_today) }} · TVSH {{ money(stats.vat_today) }}</p>
                    </div>
                    <Coins class="h-5 w-5 text-ionian shrink-0" :stroke-width="1.75" />
                </div>
            </Card>
        </div>

        <!-- ============ ALERTS ============ -->
        <div v-if="alerts.length" class="mt-4 space-y-2">
            <div v-for="(a, i) in alerts" :key="i" class="flex items-center gap-2.5 rounded-lg px-4 py-2.5 text-body-sm ring-1" :class="alertLevel[a.level] || alertLevel.warning">
                <AlertTriangle class="h-4 w-4 shrink-0" :stroke-width="2" />
                <span>{{ a.message }}</span>
            </div>
        </div>

        <!-- ============ OPERATIONAL ============ -->
        <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Arrivals -->
            <Card :padding="false">
                <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                    <h3 class="text-label text-neutral-600 uppercase tracking-wider flex items-center gap-2"><LogIn class="h-4 w-4 text-success-500" :stroke-width="1.75" /> Mbërritjet sot</h3>
                    <span class="text-body-sm font-medium text-primary-900">{{ stats.arrivals }}</span>
                </div>
                <ul class="divide-y divide-neutral-100 max-h-80 overflow-y-auto">
                    <li v-for="r in arrivals" :key="r.id" class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-neutral-50">
                        <div class="min-w-0">
                            <Link :href="route('reservations.show', r.id)" class="text-body-sm text-primary-900 font-medium hover:underline truncate block">{{ r.guest }}</Link>
                            <p class="text-tiny text-neutral-400">Dhoma {{ r.room }} · {{ r.room_type }}<span v-if="r.balance > 0" class="text-error-600"> · borxh {{ money(r.balance) }}</span></p>
                        </div>
                        <Button v-if="canUpdate && r.status === 'confirmed'" size="sm" variant="primary" @click="doCheckIn(r)">Check-in</Button>
                        <Badge v-else variant="warning" dot>Në pritje</Badge>
                    </li>
                </ul>
                <div v-if="!arrivals.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">Asnjë mbërritje sot.</div>
            </Card>

            <!-- Departures -->
            <Card :padding="false">
                <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                    <h3 class="text-label text-neutral-600 uppercase tracking-wider flex items-center gap-2"><LogOut class="h-4 w-4 text-info-500" :stroke-width="1.75" /> Nisjet sot</h3>
                    <span class="text-body-sm font-medium text-primary-900">{{ stats.departures }}</span>
                </div>
                <ul class="divide-y divide-neutral-100 max-h-80 overflow-y-auto">
                    <li v-for="r in departures" :key="r.id" class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-neutral-50">
                        <div class="min-w-0">
                            <Link :href="route('reservations.show', r.id)" class="text-body-sm text-primary-900 font-medium hover:underline truncate block">{{ r.guest }}</Link>
                            <p class="text-tiny text-neutral-400">Dhoma {{ r.room }}<span v-if="r.balance > 0" class="text-error-600"> · borxh {{ money(r.balance) }}</span></p>
                        </div>
                        <Link :href="route('reservations.show', r.id)" class="no-underline shrink-0"><Button size="sm" variant="secondary">Check-out</Button></Link>
                    </li>
                </ul>
                <div v-if="!departures.length" class="px-6 py-10 text-center text-body-sm text-neutral-500">Asnjë nisje sot.</div>
            </Card>
        </div>

        <!-- ============ ROOM BOARD + HOUSEKEEPING + MINI STATS ============ -->
        <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Room status board -->
            <Card class="lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-label text-neutral-600 uppercase tracking-wider">Bordi i dhomave</h3>
                    <div class="flex flex-wrap gap-x-4 gap-y-1">
                        <span v-for="(m, k) in roomStatusMeta" :key="k" class="inline-flex items-center gap-1.5 text-tiny text-neutral-500">
                            <span class="h-2 w-2 rounded-full" :class="m.dot" /> {{ m.label }} ({{ roomStatusCounts[k] }})
                        </span>
                    </div>
                </div>
                <div v-if="rooms.length" class="flex flex-wrap gap-1.5">
                    <span v-for="r in rooms" :key="r.room_number"
                          class="inline-flex items-center justify-center h-9 min-w-9 px-2 rounded-md text-tiny font-semibold ring-1"
                          :class="(roomStatusMeta[r.status] || roomStatusMeta.available).tile"
                          :title="`${r.room_type || ''} — ${(roomStatusMeta[r.status] || {}).label || r.status}`">
                        {{ r.room_number }}
                    </span>
                </div>
                <div v-else class="py-6 text-center text-body-sm text-neutral-500">Asnjë dhomë e konfiguruar.</div>
            </Card>

            <!-- Mini operational stats -->
            <div class="grid grid-cols-2 lg:grid-cols-1 gap-3">
                <Card>
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-success-50 flex items-center justify-center"><BedDouble class="h-5 w-5 text-success-600" :stroke-width="1.75" /></div>
                        <div><p class="text-h3 text-primary-900 leading-none">{{ stats.in_house }}</p><p class="text-tiny text-neutral-500 mt-0.5">Mysafirë në shtëpi</p></div>
                    </div>
                </Card>
                <Card>
                    <component :is="canPos ? Link : 'div'" :href="canPos ? route('pos.index') : null" class="flex items-center gap-3 no-underline">
                        <div class="h-10 w-10 rounded-lg bg-brass/10 flex items-center justify-center"><UtensilsCrossed class="h-5 w-5 text-brass" :stroke-width="1.75" /></div>
                        <div><p class="text-h3 text-primary-900 leading-none">{{ stats.open_pos }}</p><p class="text-tiny text-neutral-500 mt-0.5">Porosi POS hapur · {{ money(stats.open_pos_total) }}</p></div>
                    </component>
                </Card>
            </div>
        </div>

        <!-- Housekeeping queue -->
        <div class="mt-6">
            <Card :padding="false">
                <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                    <h3 class="text-label text-neutral-600 uppercase tracking-wider flex items-center gap-2"><Sparkles class="h-4 w-4 text-warning-500" :stroke-width="1.75" /> Radha e pastrimit</h3>
                    <Link v-if="canHousekeeping" :href="route('housekeeping.index')" class="text-tiny text-ionian hover:underline inline-flex items-center gap-1">Shiko të gjitha <ArrowRight class="h-3 w-3" /></Link>
                </div>
                <div v-if="housekeeping.length" class="flex flex-wrap gap-2 p-4">
                    <span v-for="t in housekeeping" :key="t.id" class="inline-flex items-center gap-2 rounded-lg bg-neutral-50 ring-1 ring-neutral-200/60 px-3 py-1.5 text-body-sm">
                        <span class="font-semibold text-primary-900">Dhoma {{ t.room }}</span>
                        <span class="text-tiny text-neutral-500">{{ t.assigned_to || 'pa caktuar' }}</span>
                        <Badge v-if="t.rush" variant="error" size="sm">RUSH</Badge>
                        <Badge v-else-if="t.priority === 'urgent'" variant="warning" size="sm">urgjent</Badge>
                    </span>
                </div>
                <div v-else class="px-6 py-8 text-center text-body-sm text-neutral-500">Asnjë dhomë në radhë për pastrim. ✨</div>
            </Card>
        </div>

        <!-- ============ CHARTS ============ -->
        <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Revenue 14 days -->
            <Card>
                <h3 class="text-label text-neutral-600 uppercase tracking-wider mb-4">Të ardhurat — 14 ditët e fundit</h3>
                <div class="flex items-end justify-between gap-1 h-40">
                    <div v-for="d in charts.revenue14" :key="d.date" class="flex-1 flex flex-col items-center justify-end h-full group" :title="`${dayLabel(d.date)} · Dhoma ${money(d.room)} · Bar ${money(d.pos)}`">
                        <div class="w-full flex flex-col justify-end" :style="{ height: ((d.room + d.pos) / rev14Max * 100) + '%' }">
                            <div class="w-full bg-brass/80 rounded-t-sm" :style="{ height: ((d.room + d.pos) ? d.pos / (d.room + d.pos) * 100 : 0) + '%' }" />
                            <div class="w-full bg-ionian" :style="{ height: ((d.room + d.pos) ? d.room / (d.room + d.pos) * 100 : 0) + '%' }" />
                        </div>
                        <span class="text-[9px] text-neutral-400 mt-1">{{ new Date(d.date).getDate() }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-4 mt-3 text-tiny text-neutral-500">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-ionian" /> Dhoma</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-brass/80" /> Bar/Restorant</span>
                </div>
            </Card>

            <!-- Forward occupancy -->
            <Card>
                <h3 class="text-label text-neutral-600 uppercase tracking-wider mb-4">Mbushja — 14 ditët në vijim</h3>
                <div class="flex items-end justify-between gap-1 h-40">
                    <div v-for="d in charts.occupancy14" :key="d.date" class="flex-1 flex flex-col items-center justify-end h-full" :title="`${dayLabel(d.date)} · ${d.pct}% (${d.rooms} dhoma)`">
                        <div class="w-full rounded-t-sm" :class="d.pct >= 80 ? 'bg-error-400' : d.pct >= 50 ? 'bg-ionian' : 'bg-ionian/40'" :style="{ height: Math.max(d.pct, 2) + '%' }" />
                        <span class="text-[9px] text-neutral-400 mt-1">{{ new Date(d.date).getDate() }}</span>
                    </div>
                </div>
                <p class="text-tiny text-neutral-400 mt-3">Përqindja e dhomave të zëna për çdo ditë në vijim.</p>
            </Card>
        </div>

        <!-- Channel mix -->
        <div class="mt-6">
            <Card>
                <h3 class="text-label text-neutral-600 uppercase tracking-wider mb-4">Burimi i rezervimeve — 30 ditët e fundit</h3>
                <div v-if="charts.channelMix.length" class="space-y-2.5">
                    <div v-for="c in charts.channelMix" :key="c.channel" class="flex items-center gap-3">
                        <span class="w-28 shrink-0 text-body-sm text-neutral-600 truncate">{{ channelMeta(c.channel).label }}</span>
                        <div class="flex-1 h-5 bg-neutral-100 rounded-md overflow-hidden">
                            <div class="h-full rounded-md" :style="{ width: Math.max(c.revenue / channelMax * 100, 2) + '%', backgroundColor: channelMeta(c.channel).color }" />
                        </div>
                        <span class="w-28 shrink-0 text-right text-body-sm font-medium text-primary-900">{{ money(c.revenue) }} <span class="text-tiny text-neutral-400">({{ c.count }})</span></span>
                    </div>
                </div>
                <div v-else class="py-6 text-center text-body-sm text-neutral-500">Asnjë rezervim në 30 ditët e fundit.</div>
            </Card>
        </div>

        <ToastContainer ref="toasts" />
    </AppLayout>
</template>
