<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { MapPin, Phone, Mail } from 'lucide-vue-next';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';

defineProps({ hotel: Object });

const flash = usePage().props.flash;

const form = useForm({
    name: '',
    email: '',
    message: '',
    website: '', // honeypot — must stay empty
});

function submit() {
    form.post('/contact', {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Kontakt — Villa Mucho" />
    <WebsiteLayout>
        <section class="py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h1 class="text-h1 text-primary-900">Na Kontaktoni</h1>
                    <p class="text-body text-neutral-500 mt-2">Keni pyetje? Na shkruani dhe do ju pergjigjemi sa me shpejt.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <!-- Form -->
                    <div class="bg-white rounded-2xl border border-neutral-100 p-6 sm:p-8">
                        <div v-if="flash?.success" class="mb-4 p-3 rounded-lg bg-ionian/10 border border-ionian/20 text-body-sm text-ionian-dark">
                            {{ flash.success }}
                        </div>

                        <form @submit.prevent="submit" class="space-y-4">
                            <!-- Honeypot: hidden from humans, bots fill it -->
                            <input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="absolute -left-[9999px] h-0 w-0 opacity-0" />
                            <div>
                                <label class="block text-label text-neutral-700 mb-1.5">Emri juaj *</label>
                                <input v-model="form.name" type="text" placeholder="Emri Mbiemri" class="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-body-sm focus:border-ionian focus:ring-2 focus:ring-ionian/30" />
                                <p v-if="form.errors.name" class="text-small text-error-600 mt-1">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-label text-neutral-700 mb-1.5">Email *</label>
                                <input v-model="form.email" type="email" placeholder="email@example.com" class="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-body-sm focus:border-ionian focus:ring-2 focus:ring-ionian/30" />
                                <p v-if="form.errors.email" class="text-small text-error-600 mt-1">{{ form.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-label text-neutral-700 mb-1.5">Mesazhi *</label>
                                <textarea v-model="form.message" rows="5" placeholder="Shkruani mesazhin tuaj..." class="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-body-sm focus:border-ionian focus:ring-2 focus:ring-ionian/30" />
                                <p v-if="form.errors.message" class="text-small text-error-600 mt-1">{{ form.errors.message }}</p>
                            </div>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="btn-reserve w-full"
                            >
                                {{ form.processing ? 'Duke derguar...' : 'Dergo Mesazhin' }}
                            </button>
                        </form>
                    </div>

                    <!-- Info -->
                    <div class="space-y-6">
                        <div class="bg-neutral-50 rounded-2xl p-6">
                            <h3 class="text-h4 text-primary-900 mb-4">Informacione Kontakti</h3>
                            <div class="space-y-4">
                                <div class="flex items-start gap-3.5">
                                    <MapPin class="h-5 w-5 mt-0.5 text-ionian shrink-0" :stroke-width="1.5" />
                                    <div>
                                        <p class="text-label text-ink/80">Adresa</p>
                                        <p class="text-body-sm text-ink/55">{{ hotel?.address || 'Ksamil, Sarande, Shqiperi' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3.5">
                                    <Phone class="h-5 w-5 mt-0.5 text-ionian shrink-0" :stroke-width="1.5" />
                                    <div>
                                        <p class="text-label text-ink/80">Telefon</p>
                                        <p class="text-body-sm text-ink/55">{{ hotel?.phone || '+355 69 000 0000' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3.5">
                                    <Mail class="h-5 w-5 mt-0.5 text-ionian shrink-0" :stroke-width="1.5" />
                                    <div>
                                        <p class="text-label text-ink/80">Email</p>
                                        <p class="text-body-sm text-ink/55">{{ hotel?.email || 'info@villamucho.com' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Map -->
                        <div class="rounded-2xl overflow-hidden h-64 bg-neutral-200">
                            <iframe
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d12345.67890!2d20.0!3d39.77!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMznCsDQ2JzEyLjAiTiAyMMKwMDAnMDAuMCJF!5e0!3m2!1sen!2s!4v1234567890"
                                width="100%"
                                height="100%"
                                style="border:0;"
                                allowfullscreen
                                loading="lazy"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </WebsiteLayout>
</template>
