<script setup>
import { useForm } from '@inertiajs/vue3';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import Select from '@/Components/UI/Select.vue';
import FormGroup from '@/Components/UI/FormGroup.vue';

const props = defineProps({ settings: Object, toasts: Object });

const form = useForm({
    name: props.settings.name || '',
    address: props.settings.address || '',
    phone: props.settings.phone || '',
    email: props.settings.email || '',
    timezone: props.settings.timezone || 'Europe/Tirane',
    currency: props.settings.currency || 'EUR',
    check_in_time: props.settings.check_in_time || '14:00',
    check_out_time: props.settings.check_out_time || '11:00',
    logo: null,
});

const currencyOptions = [
    { value: 'EUR', label: 'Euro (€)' },
    { value: 'ALL', label: 'Lek (L)' },
    { value: 'USD', label: 'Dollar ($)' },
    { value: 'GBP', label: 'Pound (£)' },
];

const timezoneOptions = [
    { value: 'Europe/Tirane', label: 'Europe/Tirane (CET)' },
    { value: 'Europe/Rome', label: 'Europe/Rome (CET)' },
    { value: 'Europe/London', label: 'Europe/London (GMT)' },
    { value: 'Europe/Berlin', label: 'Europe/Berlin (CET)' },
];

function submit() {
    form.put(route('settings.hotel'), {
        onSuccess: () => props.toasts?.success('Informacionet u ruajten.'),
    });
}
</script>

<template>
    <Card>
        <template #header>
            <h3 class="text-h4 text-primary-900">Informacionet e Hotelit</h3>
        </template>

        <form @submit.prevent="submit" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormGroup label="Emri i hotelit" :error="form.errors.name" required>
                    <TextInput v-model="form.name" placeholder="Hotel Demo" :error="form.errors.name" />
                </FormGroup>
                <FormGroup label="Email" :error="form.errors.email">
                    <TextInput type="email" v-model="form.email" placeholder="info@hotel.com" :error="form.errors.email" />
                </FormGroup>
                <FormGroup label="Telefon" :error="form.errors.phone">
                    <TextInput v-model="form.phone" placeholder="+355 4 234 5678" :error="form.errors.phone" />
                </FormGroup>
                <FormGroup label="Valuta" :error="form.errors.currency" required>
                    <Select v-model="form.currency" :options="currencyOptions" :error="form.errors.currency" />
                </FormGroup>
            </div>

            <FormGroup label="Adresa" :error="form.errors.address">
                <TextInput v-model="form.address" placeholder="Rruga, qyteti" :error="form.errors.address" />
            </FormGroup>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <FormGroup label="Timezone" :error="form.errors.timezone" required>
                    <Select v-model="form.timezone" :options="timezoneOptions" :error="form.errors.timezone" />
                </FormGroup>
                <FormGroup label="Ora check-in" :error="form.errors.check_in_time" required>
                    <TextInput type="time" v-model="form.check_in_time" :error="form.errors.check_in_time" />
                </FormGroup>
                <FormGroup label="Ora check-out" :error="form.errors.check_out_time" required>
                    <TextInput type="time" v-model="form.check_out_time" :error="form.errors.check_out_time" />
                </FormGroup>
            </div>

            <div class="flex justify-end pt-2">
                <Button type="submit" variant="primary" :loading="form.processing">Ruaj ndryshimet</Button>
            </div>
        </form>
    </Card>
</template>
