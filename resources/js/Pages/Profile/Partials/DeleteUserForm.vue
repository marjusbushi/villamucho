<script setup>
import { useForm } from '@inertiajs/vue3';
import { AlertTriangle, Trash2 } from 'lucide-vue-next';
import { nextTick, ref } from 'vue';
import Button from '@/Components/UI/Button.vue';
import Modal from '@/Components/UI/Modal.vue';
import TextInput from '@/Components/UI/TextInput.vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);
const form = useForm({ password: '' });

function confirmUserDeletion() {
    confirmingUserDeletion.value = true;
    nextTick(() => nextTick(() => passwordInput.value?.focus()));
}

function closeModal() {
    if (form.processing) return;
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
}

function deleteUser() {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: closeModal,
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
}
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-error-100 text-error-700">
                <Trash2 class="h-5 w-5" />
            </span>
            <div>
                <h2 class="text-body font-bold text-error-900">Zona e rrezikut</h2>
                <p class="mt-0.5 max-w-2xl text-body-sm leading-5 text-neutral-600">Fshirja e llogarisë është përfundimtare dhe heq të gjitha të dhënat e lidhura me të.</p>
            </div>
        </div>
        <Button variant="danger" class="shrink-0" @click="confirmUserDeletion">Fshi llogarinë</Button>
    </div>

    <Modal :show="confirmingUserDeletion" title="Konfirmo fshirjen" max-width="md" @close="closeModal">
        <div class="flex gap-3 rounded-lg border border-error-200 bg-error-50 p-3 text-body-sm text-error-800">
            <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0" />
            <p>Ky veprim nuk mund të zhbëhet. Shkruaj fjalëkalimin për të konfirmuar.</p>
        </div>
        <div class="mt-4">
            <label for="delete-password" class="mb-1 block text-body-sm font-semibold text-primary-900">Fjalëkalimi</label>
            <TextInput id="delete-password" ref="passwordInput" v-model="form.password" type="password" autocomplete="current-password" placeholder="Shkruaj fjalëkalimin" :error="form.errors.password" @keyup.enter="deleteUser" />
            <p v-if="form.errors.password" class="mt-1 text-tiny text-error-600">{{ form.errors.password }}</p>
        </div>
        <template #footer>
            <Button variant="ghost" :disabled="form.processing" @click="closeModal">Anulo</Button>
            <Button variant="danger" :loading="form.processing" :disabled="!form.password" @click="deleteUser">Fshi përgjithmonë</Button>
        </template>
    </Modal>
</template>
