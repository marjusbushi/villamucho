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
        <Head :title="$t('admin.generated.k_737969ef8672')" />

        <div>
            <h2 class="text-h3 text-primary-900 mb-1">{{ $t('admin.generated.k_9fdd8aa8fc78') }}</h2>
            <p class="text-body-sm text-neutral-500 mb-6">{{ $t('admin.generated.k_20e74fbc4731') }}</p>
        </div>

        <Alert v-if="status" variant="success" class="mb-4">{{ status }}</Alert>

        <form @submit.prevent="submit" class="space-y-5">
            <FormGroup :label="$t('admin.generated.k_46418004f188')" html-for="email" :error="form.errors.email" required>
                <TextInput
                    id="email"
                    type="email"
                    v-model="form.email"
                    :placeholder="$t('admin.generated.k_53da0e246d4b')"
                    :error="form.errors.email"
                    :autofocus="true"
                    autocomplete="username"
                />
            </FormGroup>

            <FormGroup :label="$t('admin.generated.k_2f77fb4fd984')" html-for="password" :error="form.errors.password" required>
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
                <Checkbox v-model="form.remember" :label="$t('admin.generated.k_fcb0f2e8be42')" />

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-body-sm text-neutral-500 hover:text-accent-600 no-underline"
                >
{{ $t('admin.generated.k_a73a425da6c4') }} </Link>
            </div>

            <Button
                type="submit"
                variant="primary"
                size="lg"
                class="w-full"
                :loading="form.processing"
                :disabled="form.processing"
            >
{{ $t('admin.generated.k_f4a86467e5c2') }} </Button>
        </form>
    </GuestLayout>
</template>
