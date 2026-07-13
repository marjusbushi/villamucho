<script setup>
import { router, useForm } from '@inertiajs/vue3';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import TextInput from '@/Components/UI/TextInput.vue';

const props = defineProps({ settings: Object, toasts: Object });

const names = {
    USD: 'Dollar amerikan', GBP: 'Paund britanik', ALL: 'Lek shqiptar',
    CHF: 'Frangë zvicerane', TRY: 'Lirë turke', JPY: 'Jen japonez',
    CAD: 'Dollar kanadez', AUD: 'Dollar australian', SEK: 'Koronë suedeze', NOK: 'Koronë norvegjeze',
};

const form = useForm({
    enabled: Boolean(props.settings.enabled),
    api_key: '',
    clear_key: false,
});

function submit() {
    form.put(route('settings.currencies'), {
        preserveScroll: true,
        onSuccess: () => {
            form.api_key = '';
            form.clear_key = false;
            props.toasts?.success('Monedhat u ruajtën.');
        },
    });
}

function refresh() {
    router.post(route('settings.currencies.refresh'), {}, { preserveScroll: true });
}
</script>

<template>
    <Card>
        <template #header>
            <div>
                <h3 class="text-h4 text-primary-900">Monedhat & kurset</h3>
                <p class="text-tiny text-neutral-500 mt-1">
                    Kurset e 10 monedhave kryesore (baza <b>EUR</b>) merren <b>1 herë në ditë</b> nga ExchangeRate-API dhe
                    përdoren nga Financa për pagesat/faturat në monedhë tjetër. Kursi ngrihet në momentin e dokumentit —
                    dokumentet e vjetra s'ndryshojnë kurrë.
                </p>
            </div>
        </template>

        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <Checkbox v-model="form.enabled" label="Aktive" />
                <span v-if="!form.enabled" class="text-tiny text-neutral-500">— pa rifreskim automatik; vlen kursi manual i skedës Financiare.</span>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-body-sm font-semibold text-primary-900 mb-1">Çelësi API (exchangerate-api.com)</label>
                    <TextInput
                        v-model="form.api_key"
                        type="password"
                        class="w-full"
                        :placeholder="settings.configured ? 'I ruajtur: ' + settings.api_key_hint + ' — plotëso vetëm për ta ndërruar' : 'Ngjit çelësin API'"
                        autocomplete="off"
                    />
                    <div v-if="settings.configured" class="mt-2">
                        <Checkbox v-model="form.clear_key" label="Hiq çelësin e ruajtur" />
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <Button variant="secondary" :disabled="!settings.configured" @click="refresh">↻ Rifresko tani</Button>
                    <span v-if="settings.updated_at" class="text-tiny text-neutral-400 pb-2">rifreskuar: {{ settings.updated_at }}</span>
                </div>
            </div>

            <!-- rates table -->
            <div>
                <h4 class="text-tiny font-bold uppercase tracking-wide text-neutral-400 mb-2">Kurset aktuale (1 € =)</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-body-sm tabular-nums">
                        <thead><tr class="text-tiny uppercase tracking-wide text-neutral-400 text-left border-b border-neutral-100">
                            <th class="py-2 pr-3">Kodi</th><th class="py-2 pr-3">Monedha</th><th class="py-2 text-right">Kursi</th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="code in settings.tracked" :key="code" class="border-b border-neutral-50 last:border-0">
                                <td class="py-2 pr-3 font-bold text-primary-900">{{ code }}</td>
                                <td class="py-2 pr-3 text-neutral-600">{{ names[code] || code }}</td>
                                <td class="py-2 text-right font-semibold">
                                    <template v-if="settings.rates[code]">{{ settings.rates[code] }}</template>
                                    <span v-else-if="code === 'ALL' && settings.fallback_all" class="text-neutral-500" title="Kursi manual (Financiare)">{{ settings.fallback_all }} <span class="text-tiny">manual</span></span>
                                    <span v-else class="text-neutral-300">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end">
                <Button :disabled="form.processing" @click="submit">Ruaj</Button>
            </div>
        </div>
    </Card>
</template>
