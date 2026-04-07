<script setup>
import { useForm } from '@inertiajs/vue3';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import FormGroup from '@/Components/UI/FormGroup.vue';

const props = defineProps({ settings: Object, toasts: Object });

const form = useForm({
    tax_rate: props.settings.tax_rate ?? 20,
    payment_methods: props.settings.payment_methods || ['cash', 'card', 'room_charge'],
    currency_symbol: props.settings.default_currency_symbol || '€',
});

const allMethods = [
    { value: 'cash', label: 'Cash' },
    { value: 'card', label: 'Karte bankare' },
    { value: 'room_charge', label: 'Room Charge (ne folio)' },
];

function toggleMethod(method) {
    const idx = form.payment_methods.indexOf(method);
    if (idx >= 0) {
        if (form.payment_methods.length > 1) form.payment_methods.splice(idx, 1);
    } else {
        form.payment_methods.push(method);
    }
}

function submit() {
    form.put(route('settings.financial'), {
        onSuccess: () => props.toasts?.success('Konfigurimet financiare u ruajten.'),
    });
}
</script>

<template>
    <Card>
        <template #header>
            <h3 class="text-h4 text-primary-900">Konfigurime Financiare</h3>
        </template>

        <form @submit.prevent="submit" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormGroup label="TVSH / Tax Rate (%)" :error="form.errors.tax_rate" required>
                    <TextInput type="number" v-model="form.tax_rate" min="0" max="100" step="0.5" :error="form.errors.tax_rate" />
                </FormGroup>
                <FormGroup label="Simboli i valutes" :error="form.errors.currency_symbol" required>
                    <TextInput v-model="form.currency_symbol" placeholder="€" maxlength="5" :error="form.errors.currency_symbol" />
                </FormGroup>
            </div>

            <FormGroup label="Metodat e pageses aktive" :error="form.errors.payment_methods">
                <div class="space-y-2">
                    <label v-for="method in allMethods" :key="method.value" class="flex items-center gap-2 text-body-sm text-neutral-700">
                        <input
                            type="checkbox"
                            :checked="form.payment_methods.includes(method.value)"
                            class="h-4 w-4 rounded border-neutral-300 text-accent-600"
                            @change="toggleMethod(method.value)"
                        />
                        {{ method.label }}
                    </label>
                </div>
            </FormGroup>

            <div class="flex justify-end pt-2">
                <Button type="submit" variant="primary" :loading="form.processing">Ruaj ndryshimet</Button>
            </div>
        </form>
    </Card>
</template>
