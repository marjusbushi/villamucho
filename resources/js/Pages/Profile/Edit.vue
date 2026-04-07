<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});
</script>

<template>
    <Head title="Profili" />

    <AppLayout>
        <PageHeader
            title="Profili"
            :breadcrumbs="[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Profili' }]"
        />

        <div class="mt-6 max-w-2xl space-y-6">
            <Card>
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                />
            </Card>

            <Card>
                <UpdatePasswordForm />
            </Card>

            <Card>
                <DeleteUserForm />
            </Card>
        </div>
    </AppLayout>
</template>
