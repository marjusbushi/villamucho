<script setup>
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { Mail, ShieldCheck, SlidersHorizontal } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});

const page = usePage();
const { locale } = useI18n();
const user = page.props.auth.user;
const tenant = page.props.tenant;

const initials = computed(() => user.name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase());

const roleLabel = computed(() => {
    const labels = locale.value === 'sq'
        ? { admin: 'Administrator', manager: 'Menaxher', receptionist: 'Recepsionist', housekeeping: 'Housekeeping' }
        : { admin: 'Administrator', manager: 'Manager', receptionist: 'Receptionist', housekeeping: 'Housekeeping' };

    return labels[user.role] || user.role || '—';
});
</script>

<template>
    <Head :title="$t('accountCenter.pageTitle')" />

    <AppLayout>
        <PageHeader
            :title="$t('accountCenter.pageTitle')"
            :breadcrumbs="[{ label: 'Dashboard', href: '/dashboard' }, { label: $t('accountCenter.pageTitle') }]"
        />
        <p class="mt-1 text-body-sm text-neutral-500">{{ $t('accountCenter.pageSubtitle') }}</p>

        <div class="mt-5 max-w-5xl space-y-5 pb-10">
            <section class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-card">
                <div class="absolute inset-y-0 left-0 w-1.5 bg-gradient-to-b from-primary-950 to-accent-600" />
                <div class="flex flex-col gap-5 p-5 pl-7 sm:flex-row sm:items-center sm:justify-between sm:p-6 sm:pl-8">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-accent-100 text-h3 font-extrabold text-accent-800 ring-4 ring-accent-50">
                            {{ initials }}
                        </div>
                        <div class="min-w-0">
                            <h2 class="truncate text-h3 font-extrabold text-primary-900">{{ user.name }}</h2>
                            <p class="mt-1 flex items-center gap-1.5 truncate text-body-sm text-neutral-500">
                                <Mail class="h-4 w-4 shrink-0" /> {{ user.email }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        <span v-if="tenant?.name" class="rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1.5 text-tiny font-semibold text-neutral-600">{{ tenant.name }}</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-success-100 bg-success-50 px-3 py-1.5 text-tiny font-bold text-success-700">
                            <ShieldCheck class="h-4 w-4" /> {{ roleLabel }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-card">
                <div class="p-5 sm:p-7">
                    <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                </div>

                <div class="border-t border-neutral-200 bg-neutral-50/30 p-5 sm:p-7">
                    <UpdatePasswordForm />
                </div>

                <div class="border-t border-neutral-200 p-5 sm:p-7">
                    <div class="flex items-start gap-3 border-b border-neutral-100 pb-5">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-accent-50 text-accent-700">
                            <SlidersHorizontal class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-body font-bold text-primary-900">{{ $t('accountCenter.preferencesTitle') }}</h2>
                            <p class="mt-0.5 text-body-sm text-neutral-500">{{ $t('accountCenter.preferencesDescription') }}</p>
                        </div>
                    </div>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="flex items-center justify-between gap-4 rounded-xl border border-neutral-200 bg-neutral-50/60 p-4">
                            <div>
                                <p class="text-body-sm font-semibold text-primary-900">{{ $t('accountCenter.language') }}</p>
                                <p class="mt-1 text-tiny text-neutral-400">SQ / EN</p>
                            </div>
                            <LanguageSwitcher class="w-fit rounded-lg border border-neutral-200 bg-white px-3 py-2 text-neutral-600 shadow-sm" />
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50/60 p-4">
                            <p class="text-body-sm font-semibold text-primary-900">{{ $t('accountCenter.timezone') }}</p>
                            <p class="mt-2 text-body-sm font-bold text-primary-900">{{ tenant?.timezone || 'Europe/Tirane' }}</p>
                            <p class="mt-1 text-tiny text-neutral-400">{{ $t('accountCenter.timezoneHint') }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-error-200 bg-error-50/40 p-5 sm:p-6">
                <DeleteUserForm />
            </section>
        </div>
    </AppLayout>
</template>
