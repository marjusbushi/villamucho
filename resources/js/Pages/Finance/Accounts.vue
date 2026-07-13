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
    accounts: Array,
    selectedId: Number,
    ledger: Array,
    baseCurrency: String,
    fxRate: Number,
    can: Object,
});

function pick(a) {
    router.get(route('finance.accounts'), { account_id: a.id }, { preserveScroll: true, preserveState: true });
}

const showTransfer = ref(false);
const transfer = useForm({ from_account_id: props.selectedId, to_account_id: null, amount: null, description: '' });
function submitTransfer() {
    transfer.post(route('finance.transfers.store'), {
        preserveScroll: true,
        onSuccess: () => { showTransfer.value = false; transfer.reset(); },
    });
}
</script>

<template>
    <AppLayout>
        <PageHeader title="Arka & Banka" :breadcrumbs="[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Financa' }, { label: 'Arka & Banka' }]">
            <template #actions>
                <Button v-if="can.transfers && accounts.length > 1" variant="secondary" @click="showTransfer = true">⇄ Transfertë</Button>
            </template>
        </PageHeader>

        <div class="px-4 sm:px-6 pb-10 space-y-4">
            <!-- account cards -->
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <button
                    v-for="a in accounts" :key="a.id" type="button"
                    class="text-left rounded-xl border p-4 bg-white transition"
                    :class="a.id === selectedId ? 'border-accent-500 ring-2 ring-accent-500/20' : 'border-neutral-200 hover:border-neutral-300'"
                    @click="pick(a)"
                >
                    <p class="text-tiny font-semibold text-neutral-500">{{ a.type === 'cash' ? '💵' : '🏦' }} {{ a.name }} <span class="text-neutral-400">({{ a.currency }})</span></p>
                    <p class="mt-1 text-h3 font-extrabold text-primary-900 tabular-nums">{{ money(a.balance, a.currency) }}</p>
                    <p v-if="a.iban" class="text-tiny text-neutral-400 truncate">{{ a.iban }}</p>
                </button>
            </div>

            <!-- ledger -->
            <Card>
                <h3 class="text-tiny font-bold uppercase tracking-wide text-neutral-400 mb-2">
                    Libri i llogarisë — {{ accounts.find((a) => a.id === selectedId)?.name }}
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-body-sm tabular-nums">
                        <thead><tr class="text-tiny uppercase tracking-wide text-neutral-400 text-left border-b border-neutral-100">
                            <th class="py-2 pr-3">Data</th><th class="py-2 pr-3">Përshkrimi</th><th class="py-2 pr-3">Burimi</th><th class="py-2 text-right pr-3">Lëvizja</th><th class="py-2 text-right">Bilanci</th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="p in ledger" :key="p.id" class="border-b border-neutral-50 last:border-0">
                                <td class="py-2.5 pr-3 whitespace-nowrap text-neutral-500">{{ p.paid_at.slice(0, 16) }}</td>
                                <td class="py-2.5 pr-3 text-primary-900">{{ p.description }}</td>
                                <td class="py-2.5 pr-3"><span class="text-tiny font-bold rounded-full px-2 py-0.5" :class="sourceBadge(p).cls">{{ sourceBadge(p).text }}</span></td>
                                <td class="py-2.5 pr-3 text-right font-semibold whitespace-nowrap" :class="p.delta >= 0 ? 'text-accent-600' : 'text-error-600'">
                                    {{ p.delta >= 0 ? '+' : '−' }} {{ money(Math.abs(p.delta), p.currency) }}
                                </td>
                                <td class="py-2.5 text-right text-neutral-600 whitespace-nowrap">{{ money(p.balance) }}</td>
                            </tr>
                            <tr v-if="!ledger.length"><td colspan="5" class="py-6 text-center text-neutral-400">Kjo llogari s'ka ende lëvizje.</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-tiny text-neutral-400 mt-3">💡 Turnet e POS-it dhe pagesat e folios derdhen vetë këtu — manualisht futen vetëm daljet e vogla dhe transfertat.</p>
            </Card>
        </div>

        <!-- transfer modal -->
        <Modal :show="showTransfer" @close="showTransfer = false">
            <div class="p-5 space-y-4">
                <h3 class="text-h4 font-bold text-primary-900">Transfertë mes llogarive</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-body-sm font-semibold text-primary-900 mb-1">Nga</label>
                        <select v-model="transfer.from_account_id" class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-body-sm">
                            <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }} ({{ money(a.balance, a.currency) }})</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-body-sm font-semibold text-primary-900 mb-1">Te</label>
                        <select v-model="transfer.to_account_id" class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-body-sm">
                            <option v-for="a in accounts.filter((x) => x.id !== transfer.from_account_id)" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-body-sm font-semibold text-primary-900 mb-1">Shuma</label>
                    <TextInput v-model="transfer.amount" type="number" min="0.01" step="0.01" class="w-full" placeholder="0.00" />
                    <p v-if="transfer.errors.amount" class="text-tiny text-error-600 mt-1">{{ transfer.errors.amount }}</p>
                </div>
                <div>
                    <label class="block text-body-sm font-semibold text-primary-900 mb-1">Përshkrimi (ops.)</label>
                    <TextInput v-model="transfer.description" class="w-full" placeholder="p.sh. depozitim i arkës" />
                </div>
                <div class="flex justify-end gap-2">
                    <Button variant="ghost" @click="showTransfer = false">Anulo</Button>
                    <Button :disabled="transfer.processing || !transfer.to_account_id || !transfer.amount" @click="submitTransfer">Kryej transfertën</Button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
