<script setup>
import Button from '@/Components/UI/Button.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { CheckCircle2, Eye, EyeOff, KeyRound } from 'lucide-vue-next';
import { ref } from 'vue';

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
    <div class="flex items-start justify-between gap-4 border-b border-neutral-100 pb-5">
        <div class="flex items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-accent-50 text-accent-700">
                <KeyRound class="h-5 w-5" />
            </span>
            <div>
                <h2 class="text-body font-bold text-primary-900">{{ $t('accountCenter.securityTitle') }}</h2>
                <p class="mt-0.5 text-body-sm text-neutral-500">{{ $t('accountCenter.securityDescription') }}</p>
            </div>
        </div>
        <button type="button" class="rounded-lg p-2 text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700" :aria-label="showPasswords ? $t('accountCenter.hidePassword') : $t('accountCenter.showPassword')" @click="showPasswords = !showPasswords">
            <EyeOff v-if="showPasswords" class="h-5 w-5" />
            <Eye v-else class="h-5 w-5" />
        </button>
    </div>

    <form class="mt-6" @submit.prevent="updatePassword">
        <div class="grid gap-4 lg:grid-cols-3">
            <div v-for="field in [
                { id: 'current_password', label: $t('accountCenter.currentPassword'), autocomplete: 'current-password', inputRef: 'current' },
                { id: 'password', label: $t('accountCenter.newPassword'), autocomplete: 'new-password', inputRef: 'new' },
                { id: 'password_confirmation', label: $t('accountCenter.confirmPassword'), autocomplete: 'new-password' },
            ]" :key="field.id">
                <label :for="field.id" class="mb-1.5 block text-body-sm font-semibold text-primary-900">{{ field.label }}</label>
                <TextInput
                    :id="field.id"
                    :ref="field.inputRef === 'current' ? (element) => currentPasswordInput = element : field.inputRef === 'new' ? (element) => passwordInput = element : undefined"
                    v-model="form[field.id]"
                    :type="showPasswords ? 'text' : 'password'"
                    :autocomplete="field.autocomplete"
                    :error="form.errors[field.id]"
                />
                <p v-if="form.errors[field.id]" class="mt-1 text-tiny text-error-600">{{ form.errors[field.id] }}</p>
            </div>
        </div>

        <div class="mt-5 flex items-center justify-end gap-3 border-t border-neutral-100 pt-5">
            <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
                <span v-if="form.recentlySuccessful" class="inline-flex items-center gap-1.5 text-body-sm font-semibold text-success-700">
                    <CheckCircle2 class="h-4 w-4" /> {{ $t('accountCenter.passwordUpdated') }}
                </span>
            </Transition>
            <Button type="submit" :loading="form.processing" :disabled="!form.current_password || !form.password || !form.password_confirmation">
                {{ $t('accountCenter.updatePassword') }}
            </Button>
        </div>
    </form>
</template>
