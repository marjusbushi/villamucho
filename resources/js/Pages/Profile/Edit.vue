<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { Mail, ShieldCheck, UserRound } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});

const user = usePage().props.auth.user;
const initials = computed(() => user.name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase());
const roleLabel = computed(() => ({
    admin: 'Administrator',
    manager: 'Menaxher',
    receptionist: 'Recepsionist',
    housekeeping: 'Housekeeping',
}[user.role] || 'Përdorues'));
</script>

<template>
    <Head title="Profili" />

    <AppLayout>
        <PageHeader
            title="Profili"
            :breadcrumbs="[{ label: 'Paneli', href: '/dashboard' }, { label: 'Profili' }]"
        />
        <p class="mt-1 text-body-sm text-neutral-500">Menaxho të dhënat personale dhe sigurinë e llogarisë.</p>

        <div class="mt-5 max-w-6xl space-y-5 pb-10">
            <section class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-card">
                <div class="h-20 bg-gradient-to-r from-primary-900 via-primary-800 to-accent-700" />
                <div class="flex flex-col gap-4 px-5 pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex min-w-0 items-end gap-4">
                        <div class="-mt-8 grid h-20 w-20 shrink-0 place-items-center rounded-2xl border-4 border-white bg-accent-100 text-h2 font-extrabold text-accent-800 shadow-sm">
                            {{ initials }}
                        </div>
                        <div class="min-w-0 pb-1">
                            <h2 class="truncate text-h3 font-extrabold text-primary-900">{{ user.name }}</h2>
                            <p class="mt-1 flex items-center gap-1.5 truncate text-body-sm text-neutral-500">
                                <Mail class="h-4 w-4 shrink-0" />
                                {{ user.email }}
                            </p>
                        </div>
                    </div>
                    <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-success-50 px-3 py-1 text-tiny font-bold text-success-700">
                        <ShieldCheck class="h-4 w-4" />
                        {{ roleLabel }}
                    </span>
                </div>
            </section>

            <div class="grid gap-5 lg:grid-cols-2 lg:items-start">
                <section class="rounded-xl border border-neutral-200 bg-white p-5 shadow-card sm:p-6">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </section>

                <section class="rounded-xl border border-neutral-200 bg-white p-5 shadow-card sm:p-6">
                    <UpdatePasswordForm />
                </section>
            </div>

            <section class="rounded-xl border border-error-200 bg-error-50/40 p-5 sm:p-6">
                <DeleteUserForm />
            </section>
        </div>
    </AppLayout>
</template>
