<script setup>
import { useForm } from '@inertiajs/vue3';
import { CheckCircle2, Eye, EyeOff, KeyRound } from 'lucide-vue-next';
import { ref } from 'vue';
import Button from '@/Components/UI/Button.vue';
import TextInput from '@/Components/UI/TextInput.vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);
const showPasswords = ref(false);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function updatePassword() {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
}
</script>

<template>
    <div class="flex items-start gap-3">
        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-accent-50 text-accent-700">
            <KeyRound class="h-5 w-5" />
        </span>
        <div>
            <h2 class="text-body font-bold text-primary-900">Siguria</h2>
            <p class="mt-0.5 text-body-sm leading-5 text-neutral-500">Përdor një fjalëkalim unik me të paktën 8 karaktere.</p>
        </div>
    </div>

    <form class="mt-6 space-y-4" @submit.prevent="updatePassword">
        <div v-for="field in [
            { id: 'current_password', label: 'Fjalëkalimi aktual', autocomplete: 'current-password', ref: 'current' },
            { id: 'password', label: 'Fjalëkalimi i ri', autocomplete: 'new-password', ref: 'new' },
            { id: 'password_confirmation', label: 'Konfirmo fjalëkalimin', autocomplete: 'new-password' },
        ]" :key="field.id">
            <label :for="field.id" class="mb-1 block text-body-sm font-semibold text-primary-900">{{ field.label }}</label>
            <div class="relative">
                <TextInput
                    :id="field.id"
                    :ref="field.ref === 'current' ? (el) => currentPasswordInput = el : field.ref === 'new' ? (el) => passwordInput = el : undefined"
                    v-model="form[field.id]"
                    :type="showPasswords ? 'text' : 'password'"
                    :autocomplete="field.autocomplete"
                    class="pr-10"
                    :error="form.errors[field.id]"
                />
                <button type="button" class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded p-1 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700" :aria-label="showPasswords ? 'Fshih fjalëkalimet' : 'Shfaq fjalëkalimet'" @click="showPasswords = !showPasswords">
                    <EyeOff v-if="showPasswords" class="h-4 w-4" />
                    <Eye v-else class="h-4 w-4" />
                </button>
            </div>
            <p v-if="form.errors[field.id]" class="mt-1 text-tiny text-error-600">{{ form.errors[field.id] }}</p>
        </div>

        <div class="flex items-center gap-3 border-t border-neutral-100 pt-5">
            <Button type="submit" :loading="form.processing" :disabled="!form.current_password || !form.password || !form.password_confirmation">Ndrysho fjalëkalimin</Button>
            <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
                <span v-if="form.recentlySuccessful" class="inline-flex items-center gap-1.5 text-body-sm font-semibold text-success-700">
                    <CheckCircle2 class="h-4 w-4" /> U ndryshua
                </span>
            </Transition>
        </div>
    </form>
</template>
