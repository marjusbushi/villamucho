<script setup>
import { translate } from '@/i18n';
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import Modal from '@/Components/UI/Modal.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import FormGroup from '@/Components/UI/FormGroup.vue';

const props = defineProps({ floors: Array, toasts: Object });

const showModal = ref(false);
const editing = ref(null);

const form = useForm({ number: '', name: '' });

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEdit(floor) {
    editing.value = floor;
    form.number = floor.number;
    form.name = floor.name;
    form.clearErrors();
    showModal.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route('settings.floors.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; props.toasts?.success(translate('admin.generated.k_f56c4289d8c0')); },
        });
    } else {
        form.post(route('settings.floors.store'), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; form.reset(); props.toasts?.success(translate('admin.generated.k_fd5c96761979')); },
        });
    }
}

function deleteFloor(floor) {
    if (!confirm(`Fshi katin "${floor.name}" (nr. ${floor.number})?`)) return;
    router.delete(route('settings.floors.destroy', floor.id), {
        preserveScroll: true,
        onSuccess: () => props.toasts?.success(translate('admin.generated.k_a26656bd65c6')),
        onError: () => props.toasts?.error(translate('admin.generated.k_0cd1dba2d2d1')),
    });
}
</script>

<template>
    <Card>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-h4 text-primary-900">{{ $t('admin.generated.k_36ec3bfbf0d0') }}</h3>
                    <p class="text-small text-neutral-500 mt-0.5">{{ $t('admin.generated.k_60d45b4d6872') }}</p>
                </div>
                <Button size="sm" variant="primary" @click="openCreate">{{ $t('admin.generated.k_d823dc0a88c6') }}</Button>
            </div>
        </template>

        <div class="divide-y divide-neutral-100">
            <div v-for="floor in floors" :key="floor.id" class="py-3.5 flex items-center gap-4">
                <Badge variant="neutral" size="sm">{{ $t('admin.generated.k_20db6fc89c14') }} {{ floor.number }}</Badge>
                <p class="flex-1 min-w-0 text-body-sm text-primary-900 font-medium truncate">{{ floor.name }}</p>
                <div class="flex gap-1.5 shrink-0">
                    <Button size="sm" variant="ghost" @click="openEdit(floor)">{{ $t('admin.generated.k_09f29a0a4755') }}</Button>
                    <Button size="sm" variant="ghost" class="text-error-600" @click="deleteFloor(floor)">{{ $t('admin.generated.k_7b4947ff2aa4') }}</Button>
                </div>
            </div>
        </div>

        <div v-if="!floors?.length" class="py-8 text-center text-body-sm text-neutral-500">
{{ $t('admin.generated.k_ceeac26bdd57') }} </div>
    </Card>

    <!-- Create/Edit Modal -->
    <Modal :show="showModal" :title="editing ? $t('admin.generated.k_8cd1b35bb357') : $t('admin.generated.k_2b06193e31ec')" @close="showModal = false">
        <form @submit.prevent="submit" class="space-y-4">
            <FormGroup :label="$t('admin.generated.k_b024a1dfff5d')" :error="form.errors.number" required>
                <TextInput type="number" v-model="form.number" min="0" max="255" :placeholder="$t('admin.generated.k_a1fe990bbca3')" :error="form.errors.number" />
            </FormGroup>
            <FormGroup :label="$t('admin.generated.k_79b8b7f6a0ba')" :error="form.errors.name" required>
                <TextInput v-model="form.name" :placeholder="$t('admin.generated.k_b179674400f1')" :error="form.errors.name" />
            </FormGroup>
        </form>
        <template #footer>
            <Button variant="outline" @click="showModal = false">{{ $t('admin.generated.k_455e902dee92') }}</Button>
            <Button variant="primary" :loading="form.processing" @click="submit">{{ editing ? $t('admin.generated.k_d9706548521d') : $t('admin.generated.k_22f07824076f') }}</Button>
        </template>
    </Modal>
</template>
