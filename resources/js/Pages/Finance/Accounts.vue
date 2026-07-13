<script setup>
import { computed, ref } from 'vue';
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
    currencies: { type: Array, default: () => ['EUR', 'ALL'] },
    can: Object,
});

// The page lists every account (management view); money can only move
// through the active ones, so the transfer dropdowns filter on is_active.
const activeAccounts = computed(() => props.accounts.filter((a) => a.is_active));

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

const showNewAccount = ref(false);
const account = useForm({ name: '', type: 'cash', currency: 'EUR', iban: '' });
function submitAccount() {
    account.post(route('finance.accounts.store'), {
        preserveScroll: true,
        onSuccess: () => { showNewAccount.value = false; account.reset(); },
    });
}

function toggleAccount(a) {
    if (a.is_active && !confirm(`Të çaktivizohet llogaria "${a.name}"? Historiku i saj ruhet dhe mund ta riaktivizosh kurdo.`)) return;
    router.put(route('finance.accounts.toggle', a.id), {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <PageHeader title="Arka & Banka" :breadcrumbs="[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Financa' }, { label: 'Arka & Banka' }]">
            <template #actions>
                <Button v-if="can.transfers && activeAccounts.length > 1" variant="secondary" @click="showTransfer = true">⇄ Transfertë</Button>
                <Button v-if="can.manageAccounts" @click="showNewAccount = true">＋ Llogari e re</Button>
            </template>
        </PageHeader>

        <div class="px-4 sm:px-6 pb-10 space-y-4">
            <!-- account cards -->
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <button
                    v-for="a in accounts" :key="a.id" type="button"
                    class="relative text-left rounded-xl border p-4 bg-white transition"
                    :class="[
                        a.id === selectedId ? 'border-accent-500 ring-2 ring-accent-500/20' : 'border-neutral-200 hover:border-neutral-300',
                        a.is_active ? '' : 'opacity-60',
                    ]"
                    @click="pick(a)"
                >
                    <p class="text-tiny font-semibold text-neutral-500">
                        {{ a.type === 'cash' ? '💵' : '🏦' }} {{ a.name }} <span class="text-neutral-400">({{ a.currency }})</span>
                        <span v-if="!a.is_active" class="ml-1 rounded-full bg-neutral-200 px-2 py-0.5 text-[10px] font-bold text-neutral-600">JOAKTIVE</span>
                    </p>
                    <p class="mt-1 text-h3 font-extrabold text-primary-900 tabular-nums">{{ money(a.balance, a.currency) }}</p>
                    <p v-if="a.iban" class="text-tiny text-neutral-400 truncate">{{ a.iban }}</p>
                    <span
                        v-if="can.manageAccounts"
                        class="absolute top-3 right-3 cursor-pointer rounded-md px-2 py-1 text-[11px] font-semibold transition-colors"
                        :class="a.is_active ? 'text-neutral-400 hover:bg-error-50 hover:text-error-600' : 'text-accent-700 hover:bg-accent-50'"
                        :title="a.is_active ? 'Çaktivizo llogarinë' : 'Riaktivizo llogarinë'"
                        @click.stop="toggleAccount(a)"
                    >{{ a.is_active ? 'Çaktivizo' : 'Riaktivizo' }}</span>
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
                            <option v-for="a in activeAccounts" :key="a.id" :value="a.id">{{ a.name }} ({{ money(a.balance, a.currency) }})</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-body-sm font-semibold text-primary-900 mb-1">Te</label>
                        <select v-model="transfer.to_account_id" class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-body-sm">
                            <option v-for="a in activeAccounts.filter((x) => x.id !== transfer.from_account_id)" :key="a.id" :value="a.id">{{ a.name }}</option>
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

        <!-- new account modal -->
        <Modal :show="showNewAccount" @close="showNewAccount = false">
            <div class="p-5 space-y-4">
                <h3 class="text-h4 font-bold text-primary-900">Llogari e re</h3>
                <div>
                    <label class="block text-body-sm font-semibold text-primary-900 mb-1">Emri</label>
                    <TextInput v-model="account.name" class="w-full" placeholder='p.sh. "Arka e Restorantit" ose "BKT"' maxlength="60" />
                    <p v-if="account.errors.name" class="text-tiny text-error-600 mt-1">{{ account.errors.name }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-body-sm font-semibold text-primary-900 mb-1">Lloji</label>
                        <select v-model="account.type" class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-body-sm">
                            <option value="cash">💵 Arkë (kesh)</option>
                            <option value="bank">🏦 Bankë</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-body-sm font-semibold text-primary-900 mb-1">Monedha</label>
                        <select v-model="account.currency" class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-body-sm">
                            <option v-for="c in currencies" :key="c" :value="c">{{ c === 'ALL' ? 'ALL (Lek)' : c }}</option>
                        </select>
                    </div>
                </div>
                <div v-if="account.type === 'bank'">
                    <label class="block text-body-sm font-semibold text-primary-900 mb-1">IBAN (ops.)</label>
                    <TextInput v-model="account.iban" class="w-full" placeholder="AL__ ____ ____ ____" maxlength="40" />
                    <p v-if="account.errors.iban" class="text-tiny text-error-600 mt-1">{{ account.errors.iban }}</p>
                </div>
                <p class="text-tiny text-neutral-400">Llogaria e re nis me bilanc 0 — lëvizjet i regjistron te Pagesat ose me Transfertë.</p>
                <div class="flex justify-end gap-2">
                    <Button variant="ghost" @click="showNewAccount = false">Anulo</Button>
                    <Button :disabled="account.processing || !account.name" @click="submitAccount">Krijo llogarinë</Button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
