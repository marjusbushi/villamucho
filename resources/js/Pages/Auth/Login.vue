<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/UI/Button.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import FormGroup from '@/Components/UI/FormGroup.vue';
import Alert from '@/Components/UI/Alert.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Hyr ne sistem" />

        <div>
            <h2 class="text-h3 text-primary-900 mb-1">Miresevini</h2>
            <p class="text-body-sm text-neutral-500 mb-6">Hyni me kredencialet tuaja per te vazhduar.</p>
        </div>

        <Alert v-if="status" variant="success" class="mb-4">{{ status }}</Alert>

        <form @submit.prevent="submit" class="space-y-5">
            <FormGroup label="Email" html-for="email" :error="form.errors.email" required>
                <TextInput
                    id="email"
                    type="email"
                    v-model="form.email"
                    placeholder="email@hotel.com"
                    :error="form.errors.email"
                    :autofocus="true"
                    autocomplete="username"
                />
            </FormGroup>

            <FormGroup label="Password" html-for="password" :error="form.errors.password" required>
                <TextInput
                    id="password"
                    type="password"
                    v-model="form.password"
                    placeholder="********"
                    :error="form.errors.password"
                    autocomplete="current-password"
                />
            </FormGroup>

            <div class="flex items-center justify-between">
                <Checkbox v-model="form.remember" label="Mbaj mend" />

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-body-sm text-neutral-500 hover:text-accent-600 no-underline"
                >
                    Harrove fjalekalimin?
                </Link>
            </div>

            <Button
                type="submit"
                variant="primary"
                size="lg"
                class="w-full"
                :loading="form.processing"
                :disabled="form.processing"
            >
                Hyr ne sistem
            </Button>
        </form>
    </GuestLayout>
</template>
