<script setup>
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { KeyRound, Mail, ShieldCheck, SlidersHorizontal, UserRound } from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
const requestedSection = typeof window !== 'undefined'
    ? new URLSearchParams(window.location.search).get('section')
    : null;
const activeSection = ref(['profile', 'security', 'preferences'].includes(requestedSection) ? requestedSection : 'profile');

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

const tabs = computed(() => [
    { id: 'profile', label: locale.value === 'sq' ? 'Profili' : 'Profile', icon: UserRound },
    { id: 'security', label: locale.value === 'sq' ? 'Siguria' : 'Security', icon: KeyRound },
    { id: 'preferences', label: locale.value === 'sq' ? 'Preferencat' : 'Preferences', icon: SlidersHorizontal },
]);
</script>

<template>
    <Head :title="$t('accountCenter.pageTitle')" />

    <AppLayout>
        <PageHeader
            :title="$t('accountCenter.pageTitle')"
            :breadcrumbs="[{ label: 'Dashboard', href: '/dashboard' }, { label: $t('accountCenter.pageTitle') }]"
        />
        <p class="mt-1 text-body-sm text-neutral-500">{{ $t('accountCenter.pageSubtitle') }}</p>

        <div class="mt-5 max-w-6xl space-y-5 pb-10">
            <section class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-card">
                <div class="h-20 bg-gradient-to-r from-primary-950 via-primary-900 to-accent-700" />
                <div class="flex flex-col gap-4 px-5 pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex min-w-0 items-end gap-4">
                        <div class="-mt-8 grid h-20 w-20 shrink-0 place-items-center rounded-2xl border-4 border-white bg-accent-100 text-h2 font-extrabold text-accent-800 shadow-sm">
                            {{ initials }}
                        </div>
                        <div class="min-w-0 pb-1">
                            <h2 class="truncate text-h3 font-extrabold text-primary-900">{{ user.name }}</h2>
                            <p class="mt-1 flex items-center gap-1.5 truncate text-body-sm text-neutral-500">
                                <Mail class="h-4 w-4 shrink-0" /> {{ user.email }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        <span v-if="tenant?.name" class="rounded-full bg-neutral-100 px-3 py-1 text-tiny font-semibold text-neutral-600">{{ tenant.name }}</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-3 py-1 text-tiny font-bold text-success-700">
                            <ShieldCheck class="h-4 w-4" /> {{ roleLabel }}
                        </span>
                    </div>
                </div>
            </section>

            <div class="grid gap-5 lg:grid-cols-[240px_minmax(0,1fr)] lg:items-start">
                <aside class="rounded-xl border border-neutral-200 bg-white p-2 shadow-card lg:sticky lg:top-20">
                    <p class="px-3 pb-1 pt-2 text-[10px] font-bold uppercase tracking-[0.12em] text-neutral-400">{{ $t('accountCenter.account') }}</p>
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-body-sm font-semibold transition"
                        :class="activeSection === tab.id ? 'bg-accent-50 text-accent-700' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900'"
                        @click="activeSection = tab.id"
                    >
                        <component :is="tab.icon" class="h-4 w-4" /> {{ tab.label }}
                    </button>
                </aside>

                <div class="min-w-0">
                    <section v-if="activeSection === 'profile'" class="rounded-xl border border-neutral-200 bg-white p-5 shadow-card sm:p-6">
                        <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                    </section>

                    <div v-else-if="activeSection === 'security'" class="space-y-5">
                        <section class="rounded-xl border border-neutral-200 bg-white p-5 shadow-card sm:p-6">
                            <UpdatePasswordForm />
                        </section>
                        <section class="rounded-xl border border-error-200 bg-error-50/40 p-5 sm:p-6">
                            <DeleteUserForm />
                        </section>
                    </div>

                    <section v-else class="rounded-xl border border-neutral-200 bg-white p-5 shadow-card sm:p-6">
                        <div class="flex items-start gap-3 border-b border-neutral-100 pb-5">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-accent-50 text-accent-700">
                                <SlidersHorizontal class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="text-body font-bold text-primary-900">{{ $t('accountCenter.preferencesTitle') }}</h2>
                                <p class="mt-0.5 text-body-sm text-neutral-500">{{ $t('accountCenter.preferencesDescription') }}</p>
                            </div>
                        </div>
                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl border border-neutral-200 p-4">
                                <p class="text-body-sm font-semibold text-primary-900">{{ $t('accountCenter.language') }}</p>
                                <LanguageSwitcher class="mt-3 w-fit rounded-lg bg-neutral-50 px-3 py-2 text-neutral-600" />
                            </div>
                            <div class="rounded-xl border border-neutral-200 p-4">
                                <p class="text-body-sm font-semibold text-primary-900">{{ $t('accountCenter.timezone') }}</p>
                                <p class="mt-3 text-body-sm font-bold text-primary-900">{{ tenant?.timezone || 'Europe/Tirane' }}</p>
                                <p class="mt-1 text-tiny text-neutral-400">{{ $t('accountCenter.timezoneHint') }}</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
