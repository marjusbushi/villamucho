<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Modal from '@/Components/UI/Modal.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import { money, sourceBadge } from './financeShared.js';

const props = defineProps({
    payments: Object, // paginator
    accounts: Array,
    filters: Object,
    baseCurrency: String,
    fxRate: Number,
    can: Object,
});

const chips = [
    { key: null, label: 'Të gjitha' },
    { key: 'in', label: '↓ Hyrje' },
    { key: 'out', label: '↑ Dalje' },
    { key: 'transfer', label: '⇄ Transferta' },
];
function filter(direction) {
    router.get(route('finance.payments'), direction ? { direction } : {}, { preserveState: true, preserveScroll: true });
}

const showNew = ref(false);
const form = useForm({
    direction: 'in', account_id: props.accounts[0]?.id, amount: null,
    currency: 'EUR', fx_rate: props.fxRate, method: 'cash', description: '', paid_at: null,
});
function submit() {
    form.post(route('finance.payments.store'), {
        preserveScroll: true,
        onSuccess: () => { showNew.value = false; form.reset('amount', 'description'); },
    });
}
</script>

<template>
    <AppLayout>
        <PageHeader title="Pagesat" :breadcrumbs="[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Financa' }, { label: 'Pagesat' }]">
            <template #actions>
                <Button v-if="can.createPayment" @click="showNew = true">+ Pagesë e re</Button>
            </template>
        </PageHeader>

        <div class="px-4 sm:px-6 pb-10 space-y-4">
            <div class="flex gap-2 flex-wrap">
                <button
                    v-for="c in chips" :key="c.label" type="button"
                    class="rounded-full border px-3.5 py-1.5 text-tiny font-semibold transition"
                    :class="(filters.direction || null) === c.key ? 'bg-primary-900 border-primary-900 text-white' : 'bg-white border-neutral-200 text-neutral-500 hover:border-neutral-300'"
                    @click="filter(c.key)"
                >{{ c.label }}</button>
            </div>

            <Card>
                <div class="overflow-x-auto">
                    <table class="w-full text-body-sm tabular-nums">
                        <thead><tr class="text-tiny uppercase tracking-wide text-neutral-400 text-left border-b border-neutral-100">
                            <th class="py-2 pr-2"></th><th class="py-2 pr-3">Data</th><th class="py-2 pr-3">Përshkrimi</th><th class="py-2 pr-3">Metoda</th><th class="py-2 pr-3">Llogaria</th><th class="py-2 pr-3">Burimi</th><th class="py-2 text-right">Shuma</th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="p in payments.data" :key="p.id" class="border-b border-neutral-50 last:border-0">
                                <td class="py-2.5 pr-2 font-extrabold" :class="p.direction === 'in' ? 'text-accent-600' : p.direction === 'out' ? 'text-error-600' : 'text-neutral-400'">
                                    {{ p.direction === 'in' ? '↓' : p.direction === 'out' ? '↑' : '⇄' }}
                                </td>
                                <td class="py-2.5 pr-3 whitespace-nowrap text-neutral-500">{{ p.paid_at.slice(0, 16) }}</td>
                                <td class="py-2.5 pr-3 text-primary-900">{{ p.description }}</td>
                                <td class="py-2.5 pr-3 text-neutral-500 uppercase text-tiny font-semibold">{{ p.method }}</td>
                                <td class="py-2.5 pr-3 text-neutral-600">{{ p.direction === 'transfer' ? p.account + ' → ' + p.counter_account : p.account }}</td>
                                <td class="py-2.5 pr-3"><span class="text-tiny font-bold rounded-full px-2 py-0.5" :class="sourceBadge(p).cls">{{ sourceBadge(p).text }}</span></td>
                                <td class="py-2.5 text-right font-semibold whitespace-nowrap" :class="p.direction === 'in' ? 'text-accent-600' : p.direction === 'out' ? 'text-error-600' : 'text-neutral-500'">
                                    {{ p.direction === 'in' ? '+' : p.direction === 'out' ? '−' : '' }} {{ money(p.amount, p.currency) }}
                                    <span v-if="p.currency !== 'EUR'" class="block text-tiny font-normal text-neutral-400">≈ {{ money(p.amount_base) }}</span>
                                </td>
                            </tr>
                            <tr v-if="!payments.data.length"><td colspan="7" class="py-6 text-center text-neutral-400">Asnjë pagesë me këto filtra.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="payments.prev_page_url || payments.next_page_url" class="flex justify-between items-center mt-3">
                    <Button variant="ghost" size="sm" :disabled="!payments.prev_page_url" @click="router.get(payments.prev_page_url, {}, { preserveState: true })">← Para</Button>
                    <span class="text-tiny text-neutral-400">Faqja {{ payments.current_page }} / {{ payments.last_page }}</span>
                    <Button variant="ghost" size="sm" :disabled="!payments.next_page_url" @click="router.get(payments.next_page_url, {}, { preserveState: true })">Pas →</Button>
                </div>
            </Card>
        </div>

        <!-- new manual payment -->
        <Modal :show="showNew" @close="showNew = false">
            <div class="p-5 space-y-4">
                <h3 class="text-h4 font-bold text-primary-900">Pagesë manuale</h3>
                <div class="flex gap-2">
                    <button type="button" class="flex-1 rounded-lg border px-3 py-2 text-body-sm font-bold" :class="form.direction === 'in' ? 'border-accent-500 bg-accent-50 text-accent-700' : 'border-neutral-200 text-neutral-500'" @click="form.direction = 'in'">↓ Hyrje (arkëtim)</button>
                    <button v-if="can.payBills" type="button" class="flex-1 rounded-lg border px-3 py-2 text-body-sm font-bold" :class="form.direction === 'out' ? 'border-error-500 bg-error-50 text-error-700' : 'border-neutral-200 text-neutral-500'" @click="form.direction = 'out'">↑ Dalje (shpenzim)</button>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-body-sm font-semibold text-primary-900 mb-1">Llogaria</label>
                        <select v-model="form.account_id" class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-body-sm">
                            <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-body-sm font-semibold text-primary-900 mb-1">Metoda</label>
                        <select v-model="form.method" class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-body-sm">
                            <option value="cash">Cash</option><option value="card">Kartë</option><option value="bank">Bankë</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-body-sm font-semibold text-primary-900 mb-1">Shuma</label>
                        <TextInput v-model="form.amount" type="number" min="0.01" step="0.01" class="w-full" placeholder="0.00" />
                        <p v-if="form.errors.amount" class="text-tiny text-error-600 mt-1">{{ form.errors.amount }}</p>
                    </div>
                    <div>
                        <label class="block text-body-sm font-semibold text-primary-900 mb-1">Monedha</label>
                        <select v-model="form.currency" class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-body-sm">
                            <option value="EUR">EUR €</option><option value="ALL">LEK</option>
                        </select>
                        <div v-if="form.currency === 'ALL'" class="mt-2">
                            <TextInput v-model="form.fx_rate" type="number" min="1" step="0.01" class="w-full" placeholder="Kursi (L për 1€)" />
                            <p v-if="form.errors.fx_rate" class="text-tiny text-error-600 mt-1">{{ form.errors.fx_rate }}</p>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-body-sm font-semibold text-primary-900 mb-1">Përshkrimi</label>
                    <TextInput v-model="form.description" class="w-full" placeholder="p.sh. blerje dekori për recepsionin" />
                    <p v-if="form.errors.description" class="text-tiny text-error-600 mt-1">{{ form.errors.description }}</p>
                </div>
                <div class="flex justify-end gap-2">
                    <Button variant="ghost" @click="showNew = false">Anulo</Button>
                    <Button :disabled="form.processing || !form.amount || !form.description" @click="submit">Ruaj</Button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
