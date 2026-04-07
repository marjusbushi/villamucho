<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import { Head, usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <PageHeader title="Dashboard" />

        <div class="mt-6">
            <Card>
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-lg bg-accent-100 flex items-center justify-center">
                        <span class="text-h4 text-accent-700">{{ user.name.charAt(0) }}</span>
                    </div>
                    <div>
                        <h3 class="text-h4 text-primary-900">Miresevini, {{ user.name }}!</h3>
                        <div class="flex items-center gap-2 mt-1">
                            <Badge variant="accent">{{ user.role }}</Badge>
                            <span class="text-body-sm text-neutral-500">{{ user.email }}</span>
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
