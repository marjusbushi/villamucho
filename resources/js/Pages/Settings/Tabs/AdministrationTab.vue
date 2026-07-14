<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowRight, History, ShieldCheck, UsersRound } from 'lucide-vue-next';
</script>

<template>
    <section>
        <div class="flex flex-col gap-3 border-b border-neutral-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-h3 text-primary-900">{{ $t('accountCenter.administration') }}</h2>
                <p class="mt-1 text-body-sm text-neutral-500">{{ $t('accountCenter.administrationDescription') }}</p>
            </div>
            <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-success-50 px-3 py-1 text-tiny font-bold text-success-700">
                <ShieldCheck class="h-4 w-4" /> Administrator
            </span>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <Link :href="route('users.index')" class="group rounded-xl border border-neutral-200 bg-white p-5 no-underline shadow-card transition hover:-translate-y-0.5 hover:border-accent-200 hover:shadow-lg">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-accent-50 text-accent-700">
                    <UsersRound class="h-5 w-5" />
                </span>
                <h3 class="mt-4 text-body font-bold text-primary-900">{{ $t('accountCenter.usersRoles') }}</h3>
                <p class="mt-1.5 min-h-10 text-body-sm leading-5 text-neutral-500">{{ $t('accountCenter.usersDescription') }}</p>
                <span class="mt-5 flex items-center justify-between border-t border-neutral-100 pt-4 text-small font-bold text-accent-700">
                    {{ $t('accountCenter.open') }} <ArrowRight class="h-4 w-4 transition group-hover:translate-x-0.5" />
                </span>
            </Link>

            <Link :href="route('audit-logs.index')" class="group rounded-xl border border-neutral-200 bg-white p-5 no-underline shadow-card transition hover:-translate-y-0.5 hover:border-accent-200 hover:shadow-lg">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-accent-50 text-accent-700">
                    <History class="h-5 w-5" />
                </span>
                <h3 class="mt-4 text-body font-bold text-primary-900">{{ $t('accountCenter.auditHistory') }}</h3>
                <p class="mt-1.5 min-h-10 text-body-sm leading-5 text-neutral-500">{{ $t('accountCenter.auditDescription') }}</p>
                <span class="mt-5 flex items-center justify-between border-t border-neutral-100 pt-4 text-small font-bold text-accent-700">
                    {{ $t('accountCenter.open') }} <ArrowRight class="h-4 w-4 transition group-hover:translate-x-0.5" />
                </span>
            </Link>
        </div>
    </section>
</template>
