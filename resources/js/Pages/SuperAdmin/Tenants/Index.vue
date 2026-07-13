<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Button from '@/Components/UI/Button.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    tenants: Array,
    currentTenantId: Number,
});

const form = useForm({
    name: '',
    slug: '',
    primary_domain: '',
    timezone: 'Europe/Tirane',
    currency: 'EUR',
});

function createTenant() {
    form.post(route('super-admin.tenants.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('name', 'slug', 'primary_domain'),
    });
}

function switchTenant(tenant) {
    router.post(route('super-admin.tenants.switch', tenant.id));
}
</script>

<template>
    <Head :title="$t('admin.generated.k_0f144fdc6f3c')" />

    <AppLayout>
        <div class="mx-auto max-w-7xl space-y-6">
            <PageHeader
                :title="$t('admin.generated.k_0f144fdc6f3c')"
                :breadcrumbs="[{ label: $t('admin.generated.k_6154ae4add9b'), href: '/dashboard' }, { label: $t('admin.generated.k_a50fd46b30d2') }]"
            />

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
                <section class="overflow-hidden rounded-xl border border-neutral-200 bg-white">
                    <div class="border-b border-neutral-200 px-5 py-4">
                        <h2 class="text-lg font-semibold text-neutral-900">{{ $t('admin.generated.k_3355ec8be5f6') }}</h2>
                        <p class="mt-1 text-sm text-neutral-500">{{ $t('admin.generated.k_8af0783bd6b7') }}</p>
                    </div>

                    <div v-if="tenants.length" class="divide-y divide-neutral-100">
                        <article v-for="tenant in tenants" :key="tenant.id" class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-neutral-900">{{ tenant.name }}</h3>
                                    <span v-if="tenant.id === currentTenantId" class="rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700">{{ $t('admin.generated.k_22a93f503d9f') }}</span>
                                    <span class="rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700">{{ tenant.status }}</span>
                                </div>
                                <p class="mt-1 text-sm text-neutral-500">{{ tenant.primary_domain || $t('admin.generated.k_80db22e21aa6') }} · {{ tenant.users_count }} {{ $t('admin.generated.k_729f0ff390ae') }}</p>
                                <p class="mt-1 text-xs text-neutral-400">{{ tenant.slug }} · {{ tenant.timezone }} · {{ tenant.currency }}</p>
                            </div>

                            <Button
                                size="sm"
                                :variant="tenant.id === currentTenantId ? 'outline' : 'primary'"
                                :disabled="tenant.id === currentTenantId"
                                @click="switchTenant(tenant)"
                            >
                                {{ tenant.id === currentTenantId ? $t('admin.generated.k_27c289112ce3') : $t('admin.generated.k_a52599e3c661') }}
                            </Button>
                        </article>
                    </div>
                </section>

                <aside class="rounded-xl border border-neutral-200 bg-white p-5">
                    <h2 class="text-lg font-semibold text-neutral-900">{{ $t('admin.generated.k_97bb35e222a5') }}</h2>
                    <p class="mt-1 text-sm text-neutral-500">{{ $t('admin.generated.k_bb3c4f231f74') }}</p>

                    <form class="mt-5 space-y-4" @submit.prevent="createTenant">
                        <label class="block text-sm font-medium text-neutral-700">
{{ $t('admin.generated.k_576b58fc705e') }} <input v-model="form.name" required class="mt-1 w-full rounded-lg border-neutral-300 text-sm" :placeholder="$t('admin.generated.k_e47fcc86e582')" />
                            <span v-if="form.errors.name" class="mt-1 block text-xs text-danger-600">{{ form.errors.name }}</span>
                        </label>

                        <label class="block text-sm font-medium text-neutral-700">
{{ $t('admin.generated.k_e8913516cd11') }} <input v-model="form.slug" required class="mt-1 w-full rounded-lg border-neutral-300 text-sm" :placeholder="$t('admin.generated.k_05d2f8add0d6')" />
                            <span v-if="form.errors.slug" class="mt-1 block text-xs text-danger-600">{{ form.errors.slug }}</span>
                        </label>

                        <label class="block text-sm font-medium text-neutral-700">
{{ $t('admin.generated.k_1c5756d1cdd0') }} <input v-model="form.primary_domain" class="mt-1 w-full rounded-lg border-neutral-300 text-sm" :placeholder="$t('admin.generated.k_20a434fbd3d4')" />
                            <span v-if="form.errors.primary_domain" class="mt-1 block text-xs text-danger-600">{{ form.errors.primary_domain }}</span>
                        </label>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-sm font-medium text-neutral-700">
{{ $t('admin.generated.k_209467b9eba8') }} <input v-model="form.timezone" required class="mt-1 w-full rounded-lg border-neutral-300 text-sm" />
                            </label>
                            <label class="block text-sm font-medium text-neutral-700">
{{ $t('admin.generated.k_265099595021') }} <input v-model="form.currency" required maxlength="3" class="mt-1 w-full rounded-lg border-neutral-300 text-sm uppercase" />
                            </label>
                        </div>

                        <Button type="submit" class="w-full justify-center" :disabled="form.processing">
                            {{ form.processing ? $t('admin.generated.k_f0c3ff038037') : $t('admin.generated.k_da7ad2a15d4e') }}
                        </Button>
                    </form>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
