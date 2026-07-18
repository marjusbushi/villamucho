<script setup>
import { translate } from '@/i18n';
import { ref, reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import Select from '@/Components/UI/Select.vue';
import FormGroup from '@/Components/UI/FormGroup.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';

const props = defineProps({
    settings: Object,
    checklistDefaults: { type: Object, default: () => ({}) },
    toasts: Object,
});

const defaultTypes = ['checkout_clean', 'stayover_clean', 'deep_clean', 'inspection'];
const initialTypes = props.settings.task_types || defaultTypes;

// Seed each type's checklist from the saved override, else the built-in default, else empty.
function seedList(type) {
    const saved = props.settings.checklists || {};
    return (saved[type] ?? props.checklistDefaults[type] ?? []).slice();
}
const initialChecklists = {};
for (const t of initialTypes) initialChecklists[t] = seedList(t);

const form = useForm({
    task_types: [...initialTypes],
    auto_create_on_checkout: props.settings.auto_create_on_checkout ?? true,
    default_priority: props.settings.default_priority || 'normal',
    checklists: initialChecklists,
});

const newType = ref('');
const newItem = reactive({});

const priorityOptions = [
    { value: 'normal', label: translate('admin.generated.k_9e8d802f7c6e') },
    { value: 'urgent', label: translate('admin.generated.k_54c70011a7d4') },
];

const humanize = (t) => t.replace(/_/g, ' ');

function addType() {
    const val = newType.value.trim().toLowerCase().replace(/\s+/g, '_');
    if (val && !form.task_types.includes(val)) {
        form.task_types.push(val);
        form.checklists[val] = seedList(val);
        newType.value = '';
    }
}

function removeType(i) {
    const t = form.task_types[i];
    form.task_types.splice(i, 1);
    delete form.checklists[t];
}

function addItem(type) {
    const val = (newItem[type] || '').trim();
    if (!val) return;
    if (!form.checklists[type]) form.checklists[type] = [];
    form.checklists[type].push(val);
    newItem[type] = '';
}

function removeItem(type, i) {
    form.checklists[type].splice(i, 1);
}

function submit() {
    form
        .transform((data) => ({
            ...data,
            checklists: Object.fromEntries(
                Object.entries(data.checklists || {}).map(([type, list]) => [
                    type,
                    (list || []).map((s) => (s || '').trim()).filter(Boolean),
                ]),
            ),
        }))
        .put(route('settings.housekeeping'), {
            onSuccess: () => props.toasts?.success(translate('admin.generated.k_9a3d5b1cfd2b')),
        });
}
</script>

<template>
    <Card>
        <template #header>
            <h3 class="text-h4 text-primary-900">{{ $t('admin.generated.k_00481eaf88bf') }}</h3>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <FormGroup :label="$t('admin.generated.k_eead14521892')">
                <div class="flex gap-2 mb-3">
                    <TextInput v-model="newType" :placeholder="$t('admin.generated.k_d48c31dd3bb7')" @keyup.enter.prevent="addType" class="flex-1" />
                    <Button type="button" size="sm" variant="outline" @click="addType">{{ $t('admin.generated.k_ae3be48fdbd6') }}</Button>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <Badge v-for="(t, i) in form.task_types" :key="i" variant="neutral">
                        {{ humanize(t) }}
                        <button type="button" class="ml-1 text-neutral-400 hover:text-error-500" @click="removeType(i)">{{ $t('admin.generated.k_f66e69d5de56') }}</button>
                    </Badge>
                </div>
            </FormGroup>

            <FormGroup :label="$t('admin.generated.k_422b5c6cdedc')">
                <p class="text-small text-neutral-500 mb-3">
{{ $t('admin.generated.k_79050067ab53') }} <strong>{{ $t('admin.generated.k_b9cfb5faadc6') }}</strong>{{ $t('admin.generated.k_7eff974129e3') }} </p>
                <div class="space-y-3">
                    <div v-for="t in form.task_types" :key="t" class="rounded-lg border border-neutral-200 p-3">
                        <p class="text-label text-neutral-700 mb-2 capitalize">{{ humanize(t) }}</p>

                        <div v-if="form.checklists[t] && form.checklists[t].length" class="space-y-1.5 mb-2">
                            <div v-for="(item, i) in form.checklists[t]" :key="i" class="flex items-center gap-2">
                                <span class="text-tiny text-neutral-400 w-5 text-right shrink-0">{{ i + 1 }}.</span>
                                <TextInput v-model="form.checklists[t][i]" class="flex-1" />
                                <button type="button" class="text-neutral-400 hover:text-error-500 px-1 shrink-0" @click="removeItem(t, i)" :aria-label="$t('admin.generated.k_6a07a8fe4e9b')">{{ $t('admin.generated.k_f66e69d5de56') }}</button>
                            </div>
                        </div>
                        <p v-else class="text-tiny text-neutral-400 mb-2">{{ $t('admin.generated.k_a151b44fd631') }}</p>

                        <div class="flex gap-2">
                            <TextInput v-model="newItem[t]" :placeholder="$t('admin.generated.k_0045f22b7f0e')" @keyup.enter.prevent="addItem(t)" class="flex-1" />
                            <Button type="button" size="sm" variant="outline" @click="addItem(t)">{{ $t('admin.generated.k_ae3be48fdbd6') }}</Button>
                        </div>
                    </div>
                </div>
            </FormGroup>

            <FormGroup :label="$t('admin.generated.k_3f7fa9cfb8d7')" :error="form.errors.default_priority">
                <Select v-model="form.default_priority" :options="priorityOptions" :error="form.errors.default_priority" />
            </FormGroup>

            <Checkbox v-model="form.auto_create_on_checkout" :label="$t('admin.generated.k_30cff834434a')" />

            <div class="settings-actions">
                <Button type="submit" variant="primary" :loading="form.processing">{{ $t('admin.generated.k_3c285306a8be') }}</Button>
            </div>
        </form>
    </Card>
</template>
