<script setup>
import { translate } from '@/i18n';
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import FormGroup from '@/Components/UI/FormGroup.vue';

const props = defineProps({ settings: Object, toasts: Object });

const form = useForm({
    instagram: props.settings.instagram || '',
    facebook: props.settings.facebook || '',
    maps_url: props.settings.maps_url || '',
    logo: null,
    hero_image: null,
});

const logoInput = ref(null);
const heroInput = ref(null);
const logoPreview = ref(null);
const heroPreview = ref(null);

const currentLogo = computed(() => props.settings.logo ? `/storage/${props.settings.logo}` : null);
const currentHero = computed(() => props.settings.hero_image ? `/storage/${props.settings.hero_image}` : null);

function onLogo(e) {
    const f = e.target.files?.[0] || null;
    form.logo = f;
    logoPreview.value = f ? URL.createObjectURL(f) : null;
}
function onHero(e) {
    const f = e.target.files?.[0] || null;
    form.hero_image = f;
    heroPreview.value = f ? URL.createObjectURL(f) : null;
}

function submit() {
    form.post(route('settings.website'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.logo = null;
            form.hero_image = null;
            logoPreview.value = null;
            heroPreview.value = null;
            if (logoInput.value) logoInput.value.value = '';
            if (heroInput.value) heroInput.value.value = '';
            props.toasts?.success(translate('admin.generated.k_e06fb28b0cde'));
        },
    });
}

const fileInputClass = 'block w-full text-small text-neutral-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-accent-600 file:text-white file:font-medium file:cursor-pointer hover:file:bg-accent-700';
</script>

<template>
    <Card>
        <template #header>
            <div>
                <h3 class="text-h4 text-primary-900">{{ $t('admin.generated.k_a9ee30a94e50') }}</h3>
                <p class="text-small text-neutral-500 mt-0.5">{{ $t('admin.generated.k_59eb2806c5fc') }}</p>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-8">
            <!-- Logo -->
            <div class="grid grid-cols-1 sm:grid-cols-[160px_1fr] gap-4 items-start">
                <div>
                    <p class="text-label text-neutral-700 mb-2">{{ $t('admin.generated.k_e22257c4e7e2') }}</p>
                    <div class="h-20 w-40 rounded-lg border border-neutral-200 bg-neutral-50 flex items-center justify-center overflow-hidden">
                        <img v-if="logoPreview || currentLogo" :src="logoPreview || currentLogo" :alt="$t('admin.generated.k_609a78feecd9')" class="max-h-full max-w-full object-contain" />
                        <span v-else class="text-tiny text-neutral-400">{{ $t('admin.generated.k_d972a5fbe9b4') }}</span>
                    </div>
                </div>
                <div>
                    <input ref="logoInput" type="file" accept="image/jpeg,image/png,image/webp" :class="fileInputClass" @change="onLogo" />
                    <p class="text-tiny text-neutral-400 mt-2">{{ $t('admin.generated.k_2a9cf67162c7') }}</p>
                    <p v-if="form.errors.logo" class="text-small text-error-600 mt-1">{{ form.errors.logo }}</p>
                </div>
            </div>

            <!-- Hero image -->
            <div class="grid grid-cols-1 sm:grid-cols-[160px_1fr] gap-4 items-start">
                <div>
                    <p class="text-label text-neutral-700 mb-2">{{ $t('admin.generated.k_eae91369c464') }}</p>
                    <div class="h-24 w-40 rounded-lg border border-neutral-200 bg-neutral-50 overflow-hidden">
                        <img v-if="heroPreview || currentHero" :src="heroPreview || currentHero" :alt="$t('admin.generated.k_168fb000ef34')" class="h-full w-full object-cover" />
                        <div v-else class="h-full w-full flex items-center justify-center text-tiny text-neutral-400">{{ $t('admin.generated.k_d460f12dfc4d') }}</div>
                    </div>
                </div>
                <div>
                    <input ref="heroInput" type="file" accept="image/jpeg,image/png,image/webp" :class="fileInputClass" @change="onHero" />
                    <p class="text-tiny text-neutral-400 mt-2">{{ $t('admin.generated.k_1eae0c21e873') }}</p>
                    <p v-if="form.errors.hero_image" class="text-small text-error-600 mt-1">{{ form.errors.hero_image }}</p>
                </div>
            </div>

            <hr class="border-neutral-100" />

            <!-- Social + map -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormGroup :label="$t('admin.generated.k_684fb1aff3da')" :error="form.errors.instagram">
                    <TextInput v-model="form.instagram" placeholder="https://instagram.com/villamucho" :error="form.errors.instagram" />
                </FormGroup>
                <FormGroup :label="$t('admin.generated.k_070692b87434')" :error="form.errors.facebook">
                    <TextInput v-model="form.facebook" placeholder="https://facebook.com/villamucho" :error="form.errors.facebook" />
                </FormGroup>
            </div>

            <FormGroup :label="$t('admin.generated.k_8174f9a4f0b0')" :error="form.errors.maps_url">
                <TextInput v-model="form.maps_url" :placeholder="$t('admin.generated.k_080ff7e9e6ce')" :error="form.errors.maps_url" />
                <p class="text-tiny text-neutral-400 mt-1.5">{{ $t('admin.generated.k_b338b59f1a88') }}</p>
            </FormGroup>

            <div class="settings-actions">
                <Button type="submit" variant="primary" :loading="form.processing">{{ $t('admin.generated.k_1620bd2141ad') }}</Button>
            </div>
        </form>
    </Card>
</template>
