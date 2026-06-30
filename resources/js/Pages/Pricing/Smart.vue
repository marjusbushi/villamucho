<script setup>
import { ref, computed, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import ToastContainer from '@/Components/UI/ToastContainer.vue';

const props = defineProps({
    roomTypes: { type: Array, default: () => [] },
    selectedTypeId: { type: [Number, String], default: null },
    days: { type: Array, default: () => [] },
    month: { type: String, default: '' },
    prevMonth: { type: String, default: '' },
    nextMonth: { type: String, default: '' },
    settings: { type: Object, default: () => ({}) },
    currency: { type: String, default: '€' },
});

const toasts = ref(null);
const typeId = ref(props.selectedTypeId);
const selected = ref(null);

const dows = ['Hë', 'Ma', 'Më', 'En', 'Pr', 'Sh', 'Di'];
const whyText = {
    peak: 'Plot — kërkesa e lartë, mund të fitosh më shumë.',
    high: 'Po mbushet shpejt — ngrije pak.',
    low: 'Bosh dhe afër — ule që të mos humbasë dhoma.',
};

const monthLabel = computed(() =>
    props.month ? new Date(props.month + 'T00:00:00').toLocaleDateString('sq-AL', { month: 'long', year: 'numeric' }) : '',
);
const leadingBlanks = computed(() => (props.days.length ? props.days[0].dow - 1 : 0));

function go(month) {
    selected.value = null;
    router.get(route('pricing.smart.index'), { room_type_id: typeId.value, month }, { preserveScroll: true });
}
function dayNum(d) { return parseInt(d.date.slice(8, 10), 10); }
function longDate(date) {
    return new Date(date + 'T00:00:00').toLocaleDateString('sq-AL', { weekday: 'long', day: '2-digit', month: 'long' });
}

const tint = {
    peak: 'bg-error-50 border-error-200 hover:border-error-300',
    high: 'bg-warning-50 border-warning-200 hover:border-warning-300',
    low: 'bg-info-50 border-info-100 hover:border-info-200',
};
const occTone = { peak: 'text-error-700', high: 'text-warning-700', low: 'text-info-700' };
const barTone = { peak: 'bg-error-500', high: 'bg-warning-500', low: 'bg-info-500' };
const tagTone = { peak: 'bg-error-600', high: 'bg-warning-600', low: 'bg-info-600' };

function pick(d) { if (d.actionable) selected.value = d; }

function apply(d) {
    router.post(route('pricing.smart.apply'), { date: d.date, room_type_id: typeId.value, price: d.suggested_price }, {
        preserveScroll: true,
        onSuccess: () => { toasts.value?.success(`Çmimi u vendos ${props.currency}${d.suggested_price} për ${longDate(d.date)}.`); selected.value = null; },
        onError: () => toasts.value?.error('Diçka shkoi keq. Provoni përsëri.'),
    });
}
function remove(d) {
    router.post(route('pricing.smart.remove'), { date: d.date, room_type_id: typeId.value }, {
        preserveScroll: true,
        onSuccess: () => { toasts.value?.success('Çmimi u rikthye te tarifa normale.'); selected.value = null; },
    });
}

watch(() => props.selectedTypeId, (v) => { typeId.value = v; });
</script>

<template>
    <AppLayout>
        <PageHeader
            title="Çmim Inteligjent"
            :breadcrumbs="[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Çmim Inteligjent' }]"
        >
            <template #actions>
                <Link href="/pms/pricing" class="no-underline"><Button variant="outline">Çmimet</Button></Link>
            </template>
        </PageHeader>

        <Card class="mt-6">
            <p class="text-body-sm text-neutral-600">
                Mbushja e dhomave → çmimi i sugjeruar. <b>Zgjedh tipin e dhomës</b>, pastaj <b>kliko një ditë me ngjyrë</b>
                për ta aplikuar. Vetëm ditët që duhen janë me ngjyrë — asgjë s'ndryshon vetë.
            </p>
        </Card>

        <div class="mt-6">
            <Card>
                <!-- room type dropdown + month nav -->
                <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                    <div class="flex items-center gap-2.5">
                        <label class="text-label text-neutral-600">Tipi i dhomës</label>
                        <select
                            v-model="typeId"
                            class="rounded-xl border border-neutral-200 px-3.5 py-2.5 text-body-sm font-medium text-primary-900 focus:border-ionian focus:ring-2 focus:ring-ionian/30 min-w-[240px]"
                            @change="go(month)"
                        >
                            <option v-for="t in roomTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-body font-semibold text-primary-900 capitalize min-w-[130px] text-center">{{ monthLabel }}</span>
                        <div class="flex gap-1.5">
                            <Button size="sm" variant="outline" @click="go(prevMonth)">‹</Button>
                            <Button size="sm" variant="outline" @click="go(nextMonth)">›</Button>
                        </div>
                    </div>
                </div>

                <div v-if="!roomTypes.length" class="py-16 text-center text-body-sm text-neutral-500">
                    Shto fillimisht tipet e dhomave te "Dhomat".
                </div>

                <template v-else>
                    <!-- weekday header -->
                    <div class="grid grid-cols-7 gap-2 mb-2">
                        <span v-for="d in dows" :key="d" class="text-tiny font-bold uppercase tracking-wide text-neutral-400 text-center">{{ d }}</span>
                    </div>

                    <!-- calendar grid -->
                    <div class="grid grid-cols-7 gap-2">
                        <div v-for="b in leadingBlanks" :key="'b' + b" />
                        <div
                            v-for="d in days"
                            :key="d.date"
                            :class="[
                                'min-h-[78px] rounded-xl border p-2 relative transition',
                                d.actionable ? [tint[d.kind], 'cursor-pointer hover:-translate-y-0.5 hover:shadow-md'] : 'bg-white border-neutral-100',
                                selected && selected.date === d.date ? 'ring-2 ring-ionian ring-offset-1' : '',
                                d.is_past ? 'opacity-50' : '',
                            ]"
                            @click="pick(d)"
                        >
                            <div class="text-body-sm font-bold text-primary-900">{{ dayNum(d) }}</div>
                            <template v-if="!d.is_past && d.total">
                                <div :class="['mt-1.5 text-tiny font-bold', d.kind ? occTone[d.kind] : 'text-neutral-400']">{{ d.occupancy_pct }}%</div>
                                <div class="mt-1 h-1 rounded-full bg-neutral-100 overflow-hidden">
                                    <i class="block h-full rounded-full" :class="d.kind ? barTone[d.kind] : 'bg-neutral-300'" :style="{ width: Math.max(d.occupancy_pct, 4) + '%' }" />
                                </div>
                            </template>
                            <span v-if="d.actionable" :class="['absolute top-2 right-2 text-tiny font-extrabold text-white px-1.5 rounded', tagTone[d.kind]]">
                                {{ d.adjustment_pct > 0 ? '↑' : '↓' }}
                            </span>
                            <span v-if="d.has_override" class="absolute bottom-1.5 right-2 text-tiny text-neutral-400" title="Çmim i vendosur">●</span>
                        </div>
                    </div>

                    <!-- legend -->
                    <div class="flex flex-wrap gap-x-5 gap-y-2 mt-5 text-tiny text-neutral-500">
                        <span><i class="inline-block w-2.5 h-2.5 rounded-sm bg-error-500 mr-1.5 align-[-1px]" /><b class="text-primary-900">Plot</b> → ngri çmimin</span>
                        <span><i class="inline-block w-2.5 h-2.5 rounded-sm bg-warning-500 mr-1.5 align-[-1px]" /><b class="text-primary-900">Po mbushet</b> → ngri pak</span>
                        <span><i class="inline-block w-2.5 h-2.5 rounded-sm bg-info-500 mr-1.5 align-[-1px]" /><b class="text-primary-900">Bosh &amp; afër</b> → ul çmimin</span>
                        <span><i class="inline-block w-2.5 h-2.5 rounded-sm bg-neutral-300 mr-1.5 align-[-1px]" />Normale → pa veprim</span>
                    </div>

                    <!-- selected day detail -->
                    <div v-if="selected" class="mt-5 border border-neutral-200 rounded-2xl overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-3 bg-neutral-50 border-b border-neutral-200">
                            <span class="text-body-sm font-semibold text-primary-900 capitalize">{{ longDate(selected.date) }}</span>
                            <span class="text-tiny text-neutral-500">Mbushja {{ selected.booked }}/{{ selected.total }} · {{ selected.occupancy_pct }}%</span>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-4 px-4 py-4">
                            <div>
                                <div class="flex items-baseline gap-3">
                                    <span class="text-body-sm text-neutral-400 line-through">{{ currency }}{{ selected.current_price }}</span>
                                    <span class="text-neutral-400">→</span>
                                    <span class="text-h3 font-extrabold text-primary-900">{{ currency }}{{ selected.suggested_price }}</span>
                                    <span :class="['text-small font-bold px-2 py-0.5 rounded-lg', selected.adjustment_pct > 0 ? 'bg-success-50 text-success-700' : 'bg-info-50 text-info-700']">
                                        {{ selected.adjustment_pct > 0 ? '+' : '' }}{{ selected.adjustment_pct }}%
                                    </span>
                                </div>
                                <p class="text-tiny text-neutral-500 mt-1">{{ whyText[selected.kind] }}</p>
                            </div>
                            <div class="flex gap-2.5">
                                <Button variant="primary" @click="apply(selected)">Apliko {{ currency }}{{ selected.suggested_price }}</Button>
                                <Button v-if="selected.has_override" variant="ghost" @click="remove(selected)">Hiq</Button>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <ToastContainer ref="toasts" />
    </AppLayout>
</template>
