<script setup>
import { Head } from '@inertiajs/vue3';
import { Waves, UtensilsCrossed } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';

defineProps({ hotel: Object });

const { t } = useI18n();
</script>

<template>
    <Head :title="$t('about.meta.title')" />
    <WebsiteLayout>
        <!-- Hero -->
        <section class="relative h-[40vh] min-h-[300px] flex items-center justify-center bg-primary-950">
            <div class="absolute inset-0 bg-gradient-to-b from-primary-950/60 to-primary-950/90 z-10" />
            <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=1920&q=80')] bg-cover bg-center" />
            <h1 class="relative z-20 text-4xl sm:text-5xl font-bold text-white">{{ $t('about.hero.title') }}</h1>
        </section>

        <section class="py-16">
            <div class="max-w-4xl mx-auto px-4 sm:px-6">
                <div class="prose prose-lg max-w-none">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center mb-16">
                        <div>
                            <h2 class="text-h2 text-primary-900 mb-4">{{ $t('about.story.title') }}</h2>
                            <p class="text-body text-neutral-600 mb-3">
                                {{ $t('about.story.paragraph1') }}
                            </p>
                            <p class="text-body text-neutral-600">
                                {{ $t('about.story.paragraph2') }}
                            </p>
                        </div>
                        <div class="relative h-64 md:h-80 overflow-hidden bg-limestone">
                            <div class="absolute inset-0 flex flex-col items-center justify-center gap-2.5 text-driftwood">
                                <Waves class="h-9 w-9" :stroke-width="1.1" />
                                <span class="eyebrow text-driftwood/80">{{ $t('about.photoSoon') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-16">
                        <div class="text-center p-8 bg-limestone/40 border border-driftwood/15">
                            <p class="text-h1 text-brass">15+</p>
                            <p class="text-body-sm text-neutral-500 mt-1">{{ $t('about.stats.rooms') }}</p>
                        </div>
                        <div class="text-center p-8 bg-limestone/40 border border-driftwood/15">
                            <p class="text-h1 text-brass">500+</p>
                            <p class="text-body-sm text-neutral-500 mt-1">{{ $t('about.stats.guests') }}</p>
                        </div>
                        <div class="text-center p-8 bg-limestone/40 border border-driftwood/15">
                            <p class="text-h1 text-brass">4.8</p>
                            <p class="text-body-sm text-neutral-500 mt-1">{{ $t('about.stats.rating') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                        <div class="relative h-64 md:h-80 overflow-hidden bg-limestone order-2 md:order-1">
                            <div class="absolute inset-0 flex flex-col items-center justify-center gap-2.5 text-driftwood">
                                <UtensilsCrossed class="h-9 w-9" :stroke-width="1.1" />
                                <span class="eyebrow text-driftwood/80">{{ $t('about.photoSoon') }}</span>
                            </div>
                        </div>
                        <div class="order-1 md:order-2">
                            <h2 class="text-h2 text-primary-900 mb-4">{{ $t('about.staff.title') }}</h2>
                            <p class="text-body text-neutral-600 mb-3">
                                {{ $t('about.staff.paragraph1') }}
                            </p>
                            <p class="text-body text-neutral-600">
                                {{ $t('about.staff.paragraph2') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </WebsiteLayout>
</template>
