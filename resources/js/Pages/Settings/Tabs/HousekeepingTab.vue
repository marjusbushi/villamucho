<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import Select from '@/Components/UI/Select.vue';
import FormGroup from '@/Components/UI/FormGroup.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';

const props = defineProps({ settings: Object, toasts: Object });

const form = useForm({
    task_types: props.settings.task_types || ['checkout_clean', 'stayover_clean', 'deep_clean', 'inspection'],
    auto_create_on_checkout: props.settings.auto_create_on_checkout ?? true,
    default_priority: props.settings.default_priority || 'normal',
});

const newType = ref('');
const priorityOptions = [
    { value: 'normal', label: 'Normal' },
    { value: 'urgent', label: 'Urgjent' },
];

function addType() {
    const val = newType.value.trim().toLowerCase().replace(/\s+/g, '_');
    if (val && !form.task_types.includes(val)) {
        form.task_types.push(val);
        newType.value = '';
    }
}

function removeType(i) {
    form.task_types.splice(i, 1);
}

function submit() {
    form.put(route('settings.housekeeping'), {
        onSuccess: () => props.toasts?.success('Konfigurimet u ruajten.'),
    });
}
</script>

<template>
    <Card>
        <template #header>
            <h3 class="text-h4 text-primary-900">Konfigurime Housekeeping</h3>
        </template>

        <form @submit.prevent="submit" class="space-y-5">
            <FormGroup label="Tipet e detyrave te pastrimit">
                <div class="flex gap-2 mb-3">
                    <TextInput v-model="newType" placeholder="psh. turndown_service" @keyup.enter.prevent="addType" class="flex-1" />
                    <Button type="button" size="sm" variant="outline" @click="addType">+ Shto</Button>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <Badge v-for="(t, i) in form.task_types" :key="i" variant="neutral">
                        {{ t.replace(/_/g, ' ') }}
                        <button type="button" class="ml-1 text-neutral-400 hover:text-error-500" @click="removeType(i)">×</button>
                    </Badge>
                </div>
            </FormGroup>

            <FormGroup label="Prioriteti default per detyra te reja" :error="form.errors.default_priority">
                <Select v-model="form.default_priority" :options="priorityOptions" :error="form.errors.default_priority" />
            </FormGroup>

            <Checkbox v-model="form.auto_create_on_checkout" label="Krijo automatikisht detyren e pastrimit kur behet check-out" />

            <div class="flex justify-end pt-2">
                <Button type="submit" variant="primary" :loading="form.processing">Ruaj ndryshimet</Button>
            </div>
        </form>
    </Card>
</template>
