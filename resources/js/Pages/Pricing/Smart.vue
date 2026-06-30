<script setup>
import { computed, ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import ToastContainer from '@/Components/UI/ToastContainer.vue';

const props = defineProps({
    suggestions: { type: Array, default: () => [] },
    settings: { type: Object, default: () => ({}) },
    currency: { type: String, default: '€' },
});

const toasts = ref(null);

const rows = computed(() =>
    [...props.suggestions].sort(
        (a, b) => a.date.localeCompare(b.date) || a.room_type_name.localeCompare(b.room_type_name),
    ),
);

function fmtDate(d) {
    return new Date(d).toLocaleDateString('sq-AL', { weekday: 'short', day: '2-digit', month: 'short' });
}

// Colour the occupancy dot: full → red (raise!), high → amber, empty → blue (discount).
function occColor(pct) {
    if (pct >= props.settings.peak_threshold) return 'error';
    if (pct >= props.settings.high_threshold) return 'warning';
    if (pct < props.settings.low_threshold) return 'info';
    return 'neutral';
}

function apply(r) {
    router.post(route('pricing.smart.apply'), { date: r.date, room_type_id: r.room_type_id, price: r.suggested_price }, {
        preserveScroll: true,
        onSuccess: () => toasts.value?.success(`Çmimi u vendos ${props.currency}${r.suggested_price} për ${fmtDate(r.date)}.`),
        onError: () => toasts.value?.error('Diçka shkoi keq. Provoni përsëri.'),
    });
}

function remove(r) {
    router.post(route('pricing.smart.remove'), { date: r.date, room_type_id: r.room_type_id }, {
        preserveScroll: true,
        onSuccess: () => toasts.value?.success('Çmimi u rikthye te tarifa normale.'),
    });
}
</script>

<template>
    <AppLayout>
        <PageHeader
            title="Çmim Inteligjent"
            :breadcrumbs="[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Çmim Inteligjent' }]"
        >
            <template #actions>
                <Link href="/pms/pricing" class="no-underline">
                    <Button variant="outline">Çmimet</Button>
                </Link>
            </template>
        </PageHeader>

        <!-- How it works -->
        <Card class="mt-6">
            <p class="text-body-sm text-neutral-600">
                Sistemi shikon <b>mbushjen</b> e çdo tipi dhome për ditët e ardhshme dhe të <b>sugjeron</b>
                të ngresh ose ulësh çmimin. Vendos ti — kliko <b>"Apliko"</b> që çmimi i sugjeruar të vendoset
                <b>vetëm për atë datë</b>, ose lëre siç është. Asgjë nuk ndryshon vetë.
            </p>
            <p class="text-tiny text-neutral-400 mt-2">
                Rregullat: mbushja ≥ {{ settings.peak_threshold }}% → +{{ settings.peak_adj }}% ·
                ≥ {{ settings.high_threshold }}% → +{{ settings.high_adj }}% ·
                &lt; {{ settings.low_threshold }}% → {{ settings.low_adj }}%
                (afër datës &amp; bosh → edhe {{ settings.lastminute_adj }}%).
            </p>
        </Card>

        <div class="mt-6">
            <Card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">Data</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">Tipi i dhomës</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">Mbushja</th>
                                <th class="px-5 py-3 text-right text-label text-neutral-600">Çmimi tani</th>
                                <th class="px-5 py-3 text-right text-label text-neutral-600">Sugjerim</th>
                                <th class="px-5 py-3 text-right text-label text-neutral-600">Veprim</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            <tr v-for="r in rows" :key="r.date + '-' + r.room_type_id" class="hover:bg-neutral-50 transition-colors duration-100">
                                <td class="px-5 py-3 text-body-sm text-primary-900 font-medium capitalize">{{ fmtDate(r.date) }}</td>
                                <td class="px-5 py-3 text-body-sm text-neutral-700">{{ r.room_type_name }}</td>
                                <td class="px-5 py-3">
                                    <Badge :variant="occColor(r.occupancy_pct)" dot>{{ r.booked }}/{{ r.total }} · {{ r.occupancy_pct }}%</Badge>
                                </td>
                                <td class="px-5 py-3 text-right text-body-sm text-neutral-500">{{ currency }}{{ r.current_price }}</td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    <span class="text-body-sm font-semibold text-primary-900">{{ currency }}{{ r.suggested_price }}</span>
                                    <span :class="['ml-2 text-tiny font-semibold', r.adjustment_pct > 0 ? 'text-success-600' : 'text-error-600']">
                                        {{ r.adjustment_pct > 0 ? '+' : '' }}{{ r.adjustment_pct }}%
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Button size="sm" variant="primary" @click="apply(r)">Apliko</Button>
                                        <Button v-if="r.has_override" size="sm" variant="ghost" @click="remove(r)">Hiq</Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!rows.length" class="px-6 py-16 text-center">
                    <p class="text-body text-neutral-600">Asnjë sugjerim tani 👍</p>
                    <p class="text-body-sm text-neutral-400 mt-1">Mbushja është në nivele normale — çmimet aktuale janë në rregull.</p>
                </div>
            </Card>
        </div>

        <ToastContainer ref="toasts" />
    </AppLayout>
</template>
