<script setup>
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
            props.toasts?.success('Faqja "Rreth Nesh" u ruajt.');
        },
    });
}

const fileInputClass = 'block w-full text-small text-neutral-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-accent-600 file:text-white file:font-medium file:cursor-pointer hover:file:bg-accent-700';
</script>

<template>
    <Card>
        <template #header>
            <div>
                <h3 class="text-h4 text-primary-900">Faqja: Rreth Nesh</h3>
                <p class="text-small text-neutral-500 mt-0.5">Tekstet dhe fotot e faqes publike <span class="font-medium">/about</span>. Shkruaji në të dyja gjuhët (Shqip + English) — vizitori i sheh sipas gjuhës që zgjedh. Bosh = teksti i parazgjedhur.</p>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-10">
            <!-- ============ HERO ============ -->
            <section class="space-y-4">
                <div>
                    <h4 class="text-label text-primary-900 uppercase tracking-wide">1 · Kreu (Hero)</h4>
                    <p class="text-tiny text-neutral-400 mt-0.5">Titulli i madh dhe fotoja e sfondit në krye të faqes.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormGroup label="Titulli — Shqip">
                        <TextInput v-model="form.hero_title_sq" placeholder="Rreth Nesh" />
                    </FormGroup>
                    <FormGroup label="Title — English">
                        <TextInput v-model="form.hero_title_en" placeholder="About Us" />
                    </FormGroup>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-[200px_1fr] gap-4 items-start">
                    <div>
                        <p class="text-label text-neutral-700 mb-2">Foto e sfondit</p>
                        <div class="h-24 w-48 rounded-lg border border-neutral-200 bg-neutral-50 overflow-hidden">
                            <img v-if="previews.hero_image || currentImage('hero_image')" :src="previews.hero_image || currentImage('hero_image')" alt="Hero" class="h-full w-full object-cover" />
                            <div v-else class="h-full w-full flex items-center justify-center text-tiny text-neutral-400 text-center px-2">Foto e parazgjedhur</div>
                        </div>
                    </div>
                    <div>
                        <input :ref="el => inputs.hero_image.value = el" type="file" accept="image/jpeg,image/png,image/webp" :class="fileInputClass" @change="(e) => onFile('hero_image', e)" />
                        <p class="text-tiny text-neutral-400 mt-2">Foto e gjerë (landscape), ideal ≥1920px. Max 6MB.</p>
                        <p v-if="form.errors.hero_image" class="text-small text-error-600 mt-1">{{ form.errors.hero_image }}</p>
                    </div>
                </div>
            </section>

            <hr class="border-neutral-100" />

            <!-- ============ STORY ============ -->
            <section class="space-y-4">
                <div>
                    <h4 class="text-label text-primary-900 uppercase tracking-wide">2 · Historia jonë</h4>
                    <p class="text-tiny text-neutral-400 mt-0.5">Titulli, dy paragrafë dhe një foto.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormGroup label="Titulli — Shqip">
                        <TextInput v-model="form.story_title_sq" placeholder="Historia jonë" />
                    </FormGroup>
                    <FormGroup label="Title — English">
                        <TextInput v-model="form.story_title_en" placeholder="Our Story" />
                    </FormGroup>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormGroup label="Paragrafi 1 — Shqip">
                        <Textarea v-model="form.story_p1_sq" :rows="4" placeholder="Tregoni historinë e hotelit..." />
                    </FormGroup>
                    <FormGroup label="Paragraph 1 — English">
                        <Textarea v-model="form.story_p1_en" :rows="4" placeholder="Tell your hotel's story..." />
                    </FormGroup>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormGroup label="Paragrafi 2 — Shqip">
                        <Textarea v-model="form.story_p2_sq" :rows="4" placeholder="Vazhdoni historinë..." />
                    </FormGroup>
                    <FormGroup label="Paragraph 2 — English">
                        <Textarea v-model="form.story_p2_en" :rows="4" placeholder="Continue the story..." />
                    </FormGroup>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-[200px_1fr] gap-4 items-start">
                    <div>
                        <p class="text-label text-neutral-700 mb-2">Fotoja e seksionit</p>
                        <div class="h-24 w-48 rounded-lg border border-neutral-200 bg-neutral-50 overflow-hidden">
                            <img v-if="previews.story_image || currentImage('story_image')" :src="previews.story_image || currentImage('story_image')" alt="Story" class="h-full w-full object-cover" />
                            <div v-else class="h-full w-full flex items-center justify-center text-tiny text-neutral-400 text-center px-2">Pa foto</div>
                        </div>
                    </div>
                    <div>
                        <input :ref="el => inputs.story_image.value = el" type="file" accept="image/jpeg,image/png,image/webp" :class="fileInputClass" @change="(e) => onFile('story_image', e)" />
                        <p class="text-tiny text-neutral-400 mt-2">JPG/PNG/WebP, max 6MB.</p>
                        <p v-if="form.errors.story_image" class="text-small text-error-600 mt-1">{{ form.errors.story_image }}</p>
                    </div>
                </div>
            </section>

            <hr class="border-neutral-100" />

            <!-- ============ STATS ============ -->
            <section class="space-y-4">
                <div>
                    <h4 class="text-label text-primary-900 uppercase tracking-wide">3 · Statistikat</h4>
                    <p class="text-tiny text-neutral-400 mt-0.5">Tre numra me etiketat e tyre (p.sh. "15+" / "Dhoma"). Lëri bosh që të mos shfaqen.</p>
                </div>
                <div v-for="n in 3" :key="n" class="grid grid-cols-1 sm:grid-cols-[120px_1fr_1fr] gap-4 items-end">
                    <FormGroup :label="`Numri ${n}`">
                        <TextInput v-model="form[`stat${n}_value`]" placeholder="15+" />
                    </FormGroup>
                    <FormGroup label="Etiketa — Shqip">
                        <TextInput v-model="form[`stat${n}_label_sq`]" placeholder="Dhoma" />
                    </FormGroup>
                    <FormGroup label="Label — English">
                        <TextInput v-model="form[`stat${n}_label_en`]" placeholder="Rooms" />
                    </FormGroup>
                </div>
            </section>

            <hr class="border-neutral-100" />

            <!-- ============ STAFF / SERVICE ============ -->
            <section class="space-y-4">
                <div>
                    <h4 class="text-label text-primary-900 uppercase tracking-wide">4 · Shërbimi / Stafi</h4>
                    <p class="text-tiny text-neutral-400 mt-0.5">Titulli, dy paragrafë dhe një foto.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormGroup label="Titulli — Shqip">
                        <TextInput v-model="form.staff_title_sq" placeholder="Shërbimi ynë" />
                    </FormGroup>
                    <FormGroup label="Title — English">
                        <TextInput v-model="form.staff_title_en" placeholder="Our Service" />
                    </FormGroup>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormGroup label="Paragrafi 1 — Shqip">
                        <Textarea v-model="form.staff_p1_sq" :rows="4" placeholder="Përshkruani shërbimin..." />
                    </FormGroup>
                    <FormGroup label="Paragraph 1 — English">
                        <Textarea v-model="form.staff_p1_en" :rows="4" placeholder="Describe your service..." />
                    </FormGroup>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormGroup label="Paragrafi 2 — Shqip">
                        <Textarea v-model="form.staff_p2_sq" :rows="4" placeholder="Vazhdoni..." />
                    </FormGroup>
                    <FormGroup label="Paragraph 2 — English">
                        <Textarea v-model="form.staff_p2_en" :rows="4" placeholder="Continue..." />
                    </FormGroup>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-[200px_1fr] gap-4 items-start">
                    <div>
                        <p class="text-label text-neutral-700 mb-2">Fotoja e seksionit</p>
                        <div class="h-24 w-48 rounded-lg border border-neutral-200 bg-neutral-50 overflow-hidden">
                            <img v-if="previews.staff_image || currentImage('staff_image')" :src="previews.staff_image || currentImage('staff_image')" alt="Staff" class="h-full w-full object-cover" />
                            <div v-else class="h-full w-full flex items-center justify-center text-tiny text-neutral-400 text-center px-2">Pa foto</div>
                        </div>
                    </div>
                    <div>
                        <input :ref="el => inputs.staff_image.value = el" type="file" accept="image/jpeg,image/png,image/webp" :class="fileInputClass" @change="(e) => onFile('staff_image', e)" />
                        <p class="text-tiny text-neutral-400 mt-2">JPG/PNG/WebP, max 6MB.</p>
                        <p v-if="form.errors.staff_image" class="text-small text-error-600 mt-1">{{ form.errors.staff_image }}</p>
                    </div>
                </div>
            </section>

            <div class="flex justify-end pt-2 sticky bottom-0 bg-white/80 backdrop-blur-sm -mx-6 px-6 py-3 border-t border-neutral-100">
                <Button type="submit" variant="primary" :loading="form.processing">Ruaj ndryshimet</Button>
            </div>
        </form>
    </Card>
</template>
