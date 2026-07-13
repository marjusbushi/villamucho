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
            <h2 class="text-body font-bold text-primary-900">Të dhënat e profilit</h2>
            <p class="mt-0.5 text-body-sm leading-5 text-neutral-500">Përditëso emrin dhe adresën e email-it të llogarisë.</p>
        </div>
    </div>

    <form class="mt-6 space-y-5" @submit.prevent="form.patch(route('profile.update'), { preserveScroll: true })">
        <div>
            <label for="name" class="mb-1 block text-body-sm font-semibold text-primary-900">Emri i plotë</label>
            <TextInput id="name" v-model="form.name" type="text" autocomplete="name" required autofocus :error="form.errors.name" />
            <p v-if="form.errors.name" class="mt-1 text-tiny text-error-600">{{ form.errors.name }}</p>
        </div>

        <div>
            <label for="email" class="mb-1 block text-body-sm font-semibold text-primary-900">Email</label>
            <TextInput id="email" v-model="form.email" type="email" autocomplete="username" required :error="form.errors.email" />
            <p v-if="form.errors.email" class="mt-1 text-tiny text-error-600">{{ form.errors.email }}</p>
        </div>

        <div v-if="mustVerifyEmail && user.email_verified_at === null" class="rounded-lg border border-warning-200 bg-warning-50 p-3 text-body-sm text-warning-800">
            Adresa e email-it nuk është verifikuar.
            <Link :href="route('verification.send')" method="post" as="button" class="font-semibold underline underline-offset-2">
                Ridërgo email-in e verifikimit.
            </Link>
            <p v-if="status === 'verification-link-sent'" class="mt-2 font-semibold text-success-700">Lidhja e re e verifikimit u dërgua.</p>
        </div>

        <div class="flex items-center gap-3 border-t border-neutral-100 pt-5">
            <Button type="submit" :loading="form.processing" :disabled="!form.isDirty">Ruaj ndryshimet</Button>
            <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
                <span v-if="form.recentlySuccessful" class="inline-flex items-center gap-1.5 text-body-sm font-semibold text-success-700">
                    <CheckCircle2 class="h-4 w-4" /> U ruajt
                </span>
            </Transition>
        </div>
    </form>
</template>
