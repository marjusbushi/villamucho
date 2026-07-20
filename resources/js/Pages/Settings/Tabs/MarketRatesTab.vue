<script setup>
import { translate } from '@/i18n';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import TextInput from '@/Components/UI/TextInput.vue';

const props = defineProps({ settings: Object, toasts: Object });

const form = useForm({
    enabled: Boolean(props.settings.enabled),
    api_key: '',
    clear_key: false,
    competitors: [...(props.settings.competitors || [])],
    frequency: props.settings.frequency || '3x_week',
    search_query: props.settings.search_query || 'Hotels Sarande Albania',
});

const newCompetitor = ref('');

function addCompetitor() {
    const name = newCompetitor.value.trim();
    if (name && !form.competitors.includes(name)) form.competitors.push(name);
    newCompetitor.value = '';
}
function removeCompetitor(i) {
    form.competitors.splice(i, 1);
}
function submit() {
    form.put(route('settings.market-rates'), {
        preserveScroll: true,
        onSuccess: () => {
            form.api_key = '';
            form.clear_key = false;
            props.toasts?.success(translate('admin.generated.k_606a5c848b6d'));
        },
    });
}
</script>

<template>
    <Card>
        <template #header>
            <div>
                <h3 class="text-h4 text-primary-900">{{ $t('admin.generated.k_1aca156422ec') }}</h3>
                <p class="text-tiny text-neutral-500 mt-1">
{{ $t('admin.generated.k_8f9e91729ecd') }} </p>
            </div>
        </template>

        <div class="space-y-6">
            <!-- toggle -->
            <div class="flex items-center gap-3">
                <Checkbox v-model="form.enabled" :label="$t('admin.generated.k_1a8cb534cbc1')" />
                <span v-if="!form.enabled" class="text-tiny text-neutral-500">{{ $t('admin.generated.k_3f62ff949002') }}</span>
            </div>

            <!-- API key -->
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-body-sm font-semibold text-primary-900 mb-1">{{ $t('admin.generated.k_66df66ca596f') }}</label>
                    <TextInput
                        v-model="form.api_key"
                        type="password"
                        class="w-full"
                        :placeholder="settings.configured ? 'I ruajtur: ' + settings.api_key_hint + $t('admin.generated.k_efa02d07eadf') : $t('admin.generated.k_7cfc98feb161')"
                        autocomplete="off"
                    />
                    <div v-if="settings.configured" class="mt-2">
                        <Checkbox v-model="form.clear_key" :label="$t('admin.generated.k_79b9e98925cc')" />
                    </div>
                </div>
                <div>
                    <label class="block text-body-sm font-semibold text-primary-900 mb-1">{{ $t('admin.generated.k_7259d5ad3fea') }}</label>
                    <select v-model="form.frequency" class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-body-sm">
                        <option value="3x_week">{{ $t('admin.generated.k_4640d0b43c30') }}</option>
                        <option value="daily">{{ $t('admin.generated.k_73732a44bd35') }}</option>
                    </select>
                    <label class="block text-body-sm font-semibold text-primary-900 mb-1 mt-3">{{ $t('admin.generated.k_319df8f21495') }}</label>
                    <TextInput v-model="form.search_query" class="w-full" :placeholder="$t('admin.generated.k_281599a72ea0')" />
                </div>
            </div>

            <!-- competitors -->
            <div>
                <label class="block text-body-sm font-semibold text-primary-900 mb-2">
{{ $t('admin.generated.k_1fd586e5e8d3') }}{{ form.competitors.length }})
                </label>
                <ul class="flex flex-wrap gap-2">
                    <li
                        v-for="(c, i) in form.competitors"
                        :key="c"
                        class="flex items-center gap-2 bg-neutral-50 border border-neutral-200 rounded-full pl-3 pr-1.5 py-1 text-body-sm text-primary-900"
                    >
                        {{ c }}
                        <button
                            type="button"
                            class="w-5 h-5 rounded-full text-neutral-400 hover:text-error-600 hover:bg-error-50 leading-none"
                            :title="$t('admin.generated.k_c5889ebd3cb9')"
                            @click="removeCompetitor(i)"
                        >
{{ $t('admin.generated.k_bfd8f5612b7e') }} </button>
                    </li>
                </ul>
                <div class="flex gap-2 mt-3">
                    <TextInput
                        v-model="newCompetitor"
                        class="flex-1"
                        :placeholder="$t('admin.generated.k_6198b1c2cdc2')"
                        @keyup.enter="addCompetitor"
                    />
                    <Button type="button" variant="secondary" @click="addCompetitor">{{ $t('admin.generated.k_cfd46faa369f') }}</Button>
                </div>
                <p class="text-tiny text-neutral-500 mt-2">
{{ $t('admin.generated.k_26c819da9004') }} </p>
            </div>

            <div class="settings-actions">
                <Button :disabled="form.processing" @click="submit">{{ $t('admin.generated.k_e6510eb3029c') }}</Button>
            </div>
        </div>
    </Card>
</template>
