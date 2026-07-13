<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, UserRound } from 'lucide-vue-next';
import Button from '@/Components/UI/Button.vue';
import TextInput from '@/Components/UI/TextInput.vue';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = usePage().props.auth.user;
const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <div class="flex items-start gap-3">
        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-accent-50 text-accent-700">
            <UserRound class="h-5 w-5" />
        </span>
        <div>
            <h2 class="text-body font-bold text-primary-900">{{ $t('admin.generated.k_e513494aadb0') }}</h2>
            <p class="mt-0.5 text-body-sm leading-5 text-neutral-500">{{ $t('admin.generated.k_42242dc6d85f') }}</p>
        </div>
    </div>

    <form class="mt-6 space-y-5" @submit.prevent="form.patch(route('profile.update'), { preserveScroll: true })">
        <div>
            <label for="name" class="mb-1 block text-body-sm font-semibold text-primary-900">{{ $t('admin.generated.k_8179fe07889b') }}</label>
            <TextInput id="name" v-model="form.name" type="text" autocomplete="name" required autofocus :error="form.errors.name" />
            <p v-if="form.errors.name" class="mt-1 text-tiny text-error-600">{{ form.errors.name }}</p>
        </div>

        <div>
            <label for="email" class="mb-1 block text-body-sm font-semibold text-primary-900">{{ $t('admin.generated.k_b512c990bda1') }}</label>
            <TextInput id="email" v-model="form.email" type="email" autocomplete="username" required :error="form.errors.email" />
            <p v-if="form.errors.email" class="mt-1 text-tiny text-error-600">{{ form.errors.email }}</p>
        </div>

        <div v-if="mustVerifyEmail && user.email_verified_at === null" class="rounded-lg border border-warning-200 bg-warning-50 p-3 text-body-sm text-warning-800">
{{ $t('admin.generated.k_167de6a63996') }} <Link :href="route('verification.send')" method="post" as="button" class="font-semibold underline underline-offset-2">
{{ $t('admin.generated.k_68c16e6d0210') }} </Link>
            <p v-if="status === 'verification-link-sent'" class="mt-2 font-semibold text-success-700">{{ $t('admin.generated.k_53935763640f') }}</p>
        </div>

        <div class="flex items-center gap-3 border-t border-neutral-100 pt-5">
            <Button type="submit" :loading="form.processing" :disabled="!form.isDirty">{{ $t('admin.generated.k_87fe1e817eb2') }}</Button>
            <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
                <span v-if="form.recentlySuccessful" class="inline-flex items-center gap-1.5 text-body-sm font-semibold text-success-700">
                    <CheckCircle2 class="h-4 w-4" /> {{ $t('admin.generated.k_d6fd1464fcf8') }} </span>
            </Transition>
        </div>
    </form>
</template>
