<script setup>
import { translate } from '@/i18n';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import FormGroup from '@/Components/UI/FormGroup.vue';

const props = defineProps({ settings: Object, toasts: Object });

const s = props.settings || {};
const form = useForm({
    // Hero
    hero_title_sq: s.hero_title_sq || '',
    hero_title_en: s.hero_title_en || '',
    // Story
    story_title_sq: s.story_title_sq || '',
    story_title_en: s.story_title_en || '',
    story_p1_sq: s.story_p1_sq || '',
    story_p1_en: s.story_p1_en || '',
    story_p2_sq: s.story_p2_sq || '',
    story_p2_en: s.story_p2_en || '',
    // Stats
    stat1_value: s.stat1_value || '',
    stat1_label_sq: s.stat1_label_sq || '',
    stat1_label_en: s.stat1_label_en || '',
    stat2_value: s.stat2_value || '',
    stat2_label_sq: s.stat2_label_sq || '',
    stat2_label_en: s.stat2_label_en || '',
    stat3_value: s.stat3_value || '',
    stat3_label_sq: s.stat3_label_sq || '',
    stat3_label_en: s.stat3_label_en || '',
    // Staff / service
    staff_title_sq: s.staff_title_sq || '',
    staff_title_en: s.staff_title_en || '',
    staff_p1_sq: s.staff_p1_sq || '',
    staff_p1_en: s.staff_p1_en || '',
    staff_p2_sq: s.staff_p2_sq || '',
    staff_p2_en: s.staff_p2_en || '',
    // Images
    hero_image: null,
    story_image: null,
    staff_image: null,
});

const inputs = { hero_image: ref(null), story_image: ref(null), staff_image: ref(null) };
const previews = ref({ hero_image: null, story_image: null, staff_image: null });

const currentImage = (key) => (props.settings?.[key] ? `/storage/${props.settings[key]}` : null);

function onFile(key, e) {
    const f = e.target.files?.[0] || null;
    form[key] = f;
    previews.value[key] = f ? URL.createObjectURL(f) : null;
}

function submit() {
    form.post(route('settings.about'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.hero_image = null;
            form.story_image = null;
            form.staff_image = null;
            previews.value = { hero_image: null, story_image: null, staff_image: null };
            Object.values(inputs).forEach((r) => { if (r.value) r.value.value = ''; });
            props.toasts?.success(translate('admin.generated.k_d1e1b93d9921'));
        },
    });
}

const fileInputClass = 'block w-full text-small text-neutral-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-accent-600 file:text-white file:font-medium file:cursor-pointer hover:file:bg-accent-700';
</script>

<template>
    <Card>
        <template #header>
            <div>
                <h3 class="text-h4 text-primary-900">{{ $t('admin.generated.k_fb181cbd6e24') }}</h3>
                <p class="text-small text-neutral-500 mt-0.5">{{ $t('admin.generated.k_ec0a4ecfd13d') }} <span class="font-medium">{{ $t('admin.generated.k_691803691ea0') }}</span>{{ $t('admin.generated.k_51f1fbb76d9e') }}</p>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-10">
            <!-- ============ HERO ============ -->
            <section class="space-y-4">
                <div>
                    <h4 class="text-label text-primary-900 uppercase tracking-wide">{{ $t('admin.generated.k_4637c04d3850') }}</h4>
                    <p class="text-tiny text-neutral-400 mt-0.5">{{ $t('admin.generated.k_068811189474') }}</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormGroup :label="$t('admin.generated.k_8d45688ea2bd')">
                        <TextInput v-model="form.hero_title_sq" :placeholder="$t('admin.generated.k_0b6270a4e3fa')" />
                    </FormGroup>
                    <FormGroup :label="$t('admin.generated.k_02c96eda9f25')">
                        <TextInput v-model="form.hero_title_en" :placeholder="$t('admin.generated.k_b66aba3287d8')" />
                    </FormGroup>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-[200px_1fr] gap-4 items-start">
                    <div>
                        <p class="text-label text-neutral-700 mb-2">{{ $t('admin.generated.k_6cac75d38043') }}</p>
                        <div class="h-24 w-48 rounded-lg border border-neutral-200 bg-neutral-50 overflow-hidden">
                            <img v-if="previews.hero_image || currentImage('hero_image')" :src="previews.hero_image || currentImage('hero_image')" :alt="$t('admin.generated.k_21f21f103c90')" class="h-full w-full object-cover" />
                            <div v-else class="h-full w-full flex items-center justify-center text-tiny text-neutral-400 text-center px-2">{{ $t('admin.generated.k_08443ff703a6') }}</div>
                        </div>
                    </div>
                    <div>
                        <input :ref="el => inputs.hero_image.value = el" type="file" accept="image/jpeg,image/png,image/webp" :class="fileInputClass" @change="(e) => onFile('hero_image', e)" />
                        <p class="text-tiny text-neutral-400 mt-2">{{ $t('admin.generated.k_ecb00a9fa4e5') }}</p>
                        <p v-if="form.errors.hero_image" class="text-small text-error-600 mt-1">{{ form.errors.hero_image }}</p>
                    </div>
                </div>
            </section>

            <hr class="border-neutral-100" />

            <!-- ============ STORY ============ -->
            <section class="space-y-4">
                <div>
                    <h4 class="text-label text-primary-900 uppercase tracking-wide">{{ $t('admin.generated.k_02d378200d89') }}</h4>
                    <p class="text-tiny text-neutral-400 mt-0.5">{{ $t('admin.generated.k_417eb8ad33d1') }}</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormGroup :label="$t('admin.generated.k_8d45688ea2bd')">
                        <TextInput v-model="form.story_title_sq" :placeholder="$t('admin.generated.k_168a60c1fe50')" />
                    </FormGroup>
                    <FormGroup :label="$t('admin.generated.k_02c96eda9f25')">
                        <TextInput v-model="form.story_title_en" :placeholder="$t('admin.generated.k_a1c361e78cd5')" />
                    </FormGroup>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormGroup :label="$t('admin.generated.k_53c82bb4f6e9')">
                        <Textarea v-model="form.story_p1_sq" :rows="4" :placeholder="$t('admin.generated.k_057bc7348f36')" />
                    </FormGroup>
                    <FormGroup :label="$t('admin.generated.k_587ac0db02bb')">
                        <Textarea v-model="form.story_p1_en" :rows="4" :placeholder="$t('admin.generated.k_9c9146b2b671')" />
                    </FormGroup>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormGroup :label="$t('admin.generated.k_3cfaf7ba0a7b')">
                        <Textarea v-model="form.story_p2_sq" :rows="4" :placeholder="$t('admin.generated.k_85033f6c7634')" />
                    </FormGroup>
                    <FormGroup :label="$t('admin.generated.k_1a76d682fba0')">
                        <Textarea v-model="form.story_p2_en" :rows="4" :placeholder="$t('admin.generated.k_d219edc0e7c1')" />
                    </FormGroup>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-[200px_1fr] gap-4 items-start">
                    <div>
                        <p class="text-label text-neutral-700 mb-2">{{ $t('admin.generated.k_8499624c3307') }}</p>
                        <div class="h-24 w-48 rounded-lg border border-neutral-200 bg-neutral-50 overflow-hidden">
                            <img v-if="previews.story_image || currentImage('story_image')" :src="previews.story_image || currentImage('story_image')" :alt="$t('admin.generated.k_209071994e89')" class="h-full w-full object-cover" />
                            <div v-else class="h-full w-full flex items-center justify-center text-tiny text-neutral-400 text-center px-2">{{ $t('admin.generated.k_7c3b3c14d79c') }}</div>
                        </div>
                    </div>
                    <div>
                        <input :ref="el => inputs.story_image.value = el" type="file" accept="image/jpeg,image/png,image/webp" :class="fileInputClass" @change="(e) => onFile('story_image', e)" />
                        <p class="text-tiny text-neutral-400 mt-2">{{ $t('admin.generated.k_8fbcaa454d79') }}</p>
                        <p v-if="form.errors.story_image" class="text-small text-error-600 mt-1">{{ form.errors.story_image }}</p>
                    </div>
                </div>
            </section>

            <hr class="border-neutral-100" />

            <!-- ============ STATS ============ -->
            <section class="space-y-4">
                <div>
                    <h4 class="text-label text-primary-900 uppercase tracking-wide">{{ $t('admin.generated.k_32f59a47a5aa') }}</h4>
                    <p class="text-tiny text-neutral-400 mt-0.5">{{ $t('admin.generated.k_85f1a49b17ad') }}</p>
                </div>
                <div v-for="n in 3" :key="n" class="grid grid-cols-1 sm:grid-cols-[120px_1fr_1fr] gap-4 items-end">
                    <FormGroup :label="`Numri ${n}`">
                        <TextInput v-model="form[`stat${n}_value`]" placeholder="15+" />
                    </FormGroup>
                    <FormGroup :label="$t('admin.generated.k_c0afc8ea7596')">
                        <TextInput v-model="form[`stat${n}_label_sq`]" :placeholder="$t('admin.generated.k_d9ba0da91e75')" />
                    </FormGroup>
                    <FormGroup :label="$t('admin.generated.k_b58fa197929b')">
                        <TextInput v-model="form[`stat${n}_label_en`]" :placeholder="$t('admin.generated.k_77ae4d491eb2')" />
                    </FormGroup>
                </div>
            </section>

            <hr class="border-neutral-100" />

            <!-- ============ STAFF / SERVICE ============ -->
            <section class="space-y-4">
                <div>
                    <h4 class="text-label text-primary-900 uppercase tracking-wide">{{ $t('admin.generated.k_dd648960f34e') }}</h4>
                    <p class="text-tiny text-neutral-400 mt-0.5">{{ $t('admin.generated.k_417eb8ad33d1') }}</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormGroup :label="$t('admin.generated.k_8d45688ea2bd')">
                        <TextInput v-model="form.staff_title_sq" :placeholder="$t('admin.generated.k_1dc7923873a7')" />
                    </FormGroup>
                    <FormGroup :label="$t('admin.generated.k_02c96eda9f25')">
                        <TextInput v-model="form.staff_title_en" :placeholder="$t('admin.generated.k_16e939dcde3c')" />
                    </FormGroup>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormGroup :label="$t('admin.generated.k_53c82bb4f6e9')">
                        <Textarea v-model="form.staff_p1_sq" :rows="4" :placeholder="$t('admin.generated.k_8b9daf3ccaa1')" />
                    </FormGroup>
                    <FormGroup :label="$t('admin.generated.k_587ac0db02bb')">
                        <Textarea v-model="form.staff_p1_en" :rows="4" :placeholder="$t('admin.generated.k_1e491c380e78')" />
                    </FormGroup>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormGroup :label="$t('admin.generated.k_3cfaf7ba0a7b')">
                        <Textarea v-model="form.staff_p2_sq" :rows="4" :placeholder="$t('admin.generated.k_06b1186b6c4f')" />
                    </FormGroup>
                    <FormGroup :label="$t('admin.generated.k_1a76d682fba0')">
                        <Textarea v-model="form.staff_p2_en" :rows="4" :placeholder="$t('admin.generated.k_71fa4a51181d')" />
                    </FormGroup>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-[200px_1fr] gap-4 items-start">
                    <div>
                        <p class="text-label text-neutral-700 mb-2">{{ $t('admin.generated.k_8499624c3307') }}</p>
                        <div class="h-24 w-48 rounded-lg border border-neutral-200 bg-neutral-50 overflow-hidden">
                            <img v-if="previews.staff_image || currentImage('staff_image')" :src="previews.staff_image || currentImage('staff_image')" :alt="$t('admin.generated.k_ebeb2c8c5e85')" class="h-full w-full object-cover" />
                            <div v-else class="h-full w-full flex items-center justify-center text-tiny text-neutral-400 text-center px-2">{{ $t('admin.generated.k_7c3b3c14d79c') }}</div>
                        </div>
                    </div>
                    <div>
                        <input :ref="el => inputs.staff_image.value = el" type="file" accept="image/jpeg,image/png,image/webp" :class="fileInputClass" @change="(e) => onFile('staff_image', e)" />
                        <p class="text-tiny text-neutral-400 mt-2">{{ $t('admin.generated.k_8fbcaa454d79') }}</p>
                        <p v-if="form.errors.staff_image" class="text-small text-error-600 mt-1">{{ form.errors.staff_image }}</p>
                    </div>
                </div>
            </section>

            <div class="flex justify-end pt-2 sticky bottom-0 bg-white/80 backdrop-blur-sm -mx-6 px-6 py-3 border-t border-neutral-100">
                <Button type="submit" variant="primary" :loading="form.processing">{{ $t('admin.generated.k_3f4e7027d585') }}</Button>
            </div>
        </form>
    </Card>
</template>
