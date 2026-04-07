<script setup>
import { Head } from '@inertiajs/vue3';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';

defineProps({ hotel: Object });
</script>

<template>
    <Head title="Rreth Nesh — Villa Mucho" />
    <WebsiteLayout>
        <!-- Hero -->
        <section class="relative h-[40vh] min-h-[300px] flex items-center justify-center bg-primary-950">
            <div class="absolute inset-0 bg-gradient-to-b from-primary-950/60 to-primary-950/90 z-10" />
            <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=1920&q=80')] bg-cover bg-center" />
            <h1 class="relative z-20 text-4xl sm:text-5xl font-bold text-white">Rreth Nesh</h1>
        </section>

        <section class="py-16">
            <div class="max-w-4xl mx-auto px-4 sm:px-6">
                <div class="prose prose-lg max-w-none">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center mb-16">
                        <div>
                            <h2 class="text-h2 text-primary-900 mb-4">Historia Jone</h2>
                            <p class="text-body text-neutral-600 mb-3">
                                Villa Mucho lindi nga dashuria per mikpritjen dhe bukurine e Jugut te Shqiperise. E vendosur ne brigjet e Ksamilit, villa jone ofron nje pervojë unike qe kombinon luksin modern me traditen shqiptare.
                            </p>
                            <p class="text-body text-neutral-600">
                                Me dhoma te rehatshme, restorant me kuzhine mesdhetare, dhe staf te perkushtuar, ne sigurojme qe cdo mysafir te ndihet si ne shtepi — por me mire.
                            </p>
                        </div>
                        <div class="h-64 md:h-80 rounded-2xl bg-gradient-to-br from-accent-100 to-neutral-100 flex items-center justify-center">
                            <span class="text-7xl">🏖️</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-16">
                        <div class="text-center p-6 bg-neutral-50 rounded-xl">
                            <p class="text-h1 text-accent-600">15+</p>
                            <p class="text-body-sm text-neutral-500 mt-1">Dhoma te rehatshme</p>
                        </div>
                        <div class="text-center p-6 bg-neutral-50 rounded-xl">
                            <p class="text-h1 text-accent-600">500+</p>
                            <p class="text-body-sm text-neutral-500 mt-1">Mysafire te kenaqur</p>
                        </div>
                        <div class="text-center p-6 bg-neutral-50 rounded-xl">
                            <p class="text-h1 text-accent-600">4.8</p>
                            <p class="text-body-sm text-neutral-500 mt-1">Vleresim mesatar</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                        <div class="h-64 md:h-80 rounded-2xl bg-gradient-to-br from-accent-50 to-neutral-100 flex items-center justify-center order-2 md:order-1">
                            <span class="text-7xl">👨‍🍳</span>
                        </div>
                        <div class="order-1 md:order-2">
                            <h2 class="text-h2 text-primary-900 mb-4">Stafi Yne</h2>
                            <p class="text-body text-neutral-600 mb-3">
                                Ekipi yne i perkushtuar punon cdo dite per te siguruar pervojen me te mire. Nga receptionist-et e buzequmur deri te kuzhinier-et e talentuar, secili anetar i stafit tone jep kontribut per te bere qendrimin tuaj te paharrueshëm.
                            </p>
                            <p class="text-body text-neutral-600">
                                Ne besojme ne mikpritjen e vertete — jo vetem sherbim, por kujdes i sinqerte per cdo mysafir.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </WebsiteLayout>
</template>
