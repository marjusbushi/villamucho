<script setup>
import { Head, Link } from '@inertiajs/vue3';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';

defineProps({
    roomTypes: Array,
    hotel: Object,
});

const features = [
    { icon: '🌊', title: 'Afer Detit', desc: 'Vetem 2 minuta ne kembe nga plazhi i Ksamilit' },
    { icon: '🍽️', title: 'Restorant', desc: 'Kuzhine mesdhetare me produkte lokale te fresketa' },
    { icon: '📶', title: 'WiFi Falas', desc: 'Internet i shpejte ne te gjitha ambientet' },
    { icon: '🅿️', title: 'Parking', desc: 'Parking privat falas per te gjithe mysafiret' },
];

const amenityIcons = {
    'WiFi': '📶', 'TV': '📺', 'Aire kondicionuar': '❄️', 'Banjo private': '🚿',
    'Ballkon': '🌅', 'Minibar': '🍹', 'Pamje nga deti': '🌊', 'Makineri kafeje': '☕',
};
</script>

<template>
    <Head title="Home — Villa Mucho" />
    <WebsiteLayout>
        <!-- Hero -->
        <section class="relative h-[85vh] min-h-[600px] flex items-center justify-center bg-primary-950 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-b from-primary-950/70 via-primary-950/50 to-primary-950/80 z-10" />
            <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1920&q=80')] bg-cover bg-center" />
            <div class="relative z-20 text-center px-4 max-w-3xl">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white leading-tight tracking-tight">
                    Miresevini ne<br><span class="text-accent-400">Villa Mucho</span>
                </h1>
                <p class="text-lg sm:text-xl text-neutral-300 mt-4 max-w-xl mx-auto">
                    Eksperience unike ne brigjet e Ksamilit — relaksim, natyra dhe mikpritje shqiptare.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mt-8">
                    <Link href="/book" class="px-8 py-3.5 rounded-lg bg-accent-600 text-white text-body font-medium hover:bg-accent-700 transition-all shadow-lg hover:shadow-xl no-underline">
                        Rezervo Tani
                    </Link>
                    <Link href="/rooms" class="px-8 py-3.5 rounded-lg border border-white/30 text-white text-body font-medium hover:bg-white/10 transition-all no-underline">
                        Shiko Dhomat
                    </Link>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="py-20 bg-neutral-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-h2 text-primary-900">Pse Villa Mucho?</h2>
                    <p class="text-body text-neutral-500 mt-2 max-w-lg mx-auto">Cdo detaj eshte menduar per komoditetin tuaj</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="f in features" :key="f.title" class="bg-white rounded-xl p-6 text-center border border-neutral-100 hover:shadow-md transition-shadow">
                        <span class="text-4xl block mb-3">{{ f.icon }}</span>
                        <h3 class="text-h4 text-primary-900 mb-1">{{ f.title }}</h3>
                        <p class="text-body-sm text-neutral-500">{{ f.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Room Types Preview -->
        <section class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-h2 text-primary-900">Dhomat Tona</h2>
                    <p class="text-body text-neutral-500 mt-2">Zgjidhni pervojen qe ju pershtatet</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="room in roomTypes" :key="room.id" class="group bg-white rounded-xl border border-neutral-100 overflow-hidden hover:shadow-lg transition-all duration-200">
                        <!-- Featured image -->
                        <div class="h-48 bg-gradient-to-br from-accent-100 to-neutral-100 flex items-center justify-center overflow-hidden">
                            <img v-if="room.images?.length" :src="`/storage/${room.images[0].path}`" :alt="room.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            <span v-else class="text-5xl">🏨</span>
                        </div>
                        <div class="p-5">
                            <div class="flex items-start justify-between">
                                <h3 class="text-h4 text-primary-900">{{ room.name }}</h3>
                                <div class="text-right">
                                    <p class="text-h4 text-accent-600">€{{ room.base_price }}</p>
                                    <p class="text-tiny text-neutral-400">/nate</p>
                                </div>
                            </div>
                            <p class="text-body-sm text-neutral-500 mt-2 line-clamp-2">{{ room.description }}</p>
                            <!-- Amenities -->
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                <span v-for="a in (room.amenities || []).slice(0, 4)" :key="a" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-neutral-50 text-tiny text-neutral-600">
                                    {{ amenityIcons[a] || '✓' }} {{ a }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between mt-4 pt-4 border-t border-neutral-100">
                                <span class="text-small text-neutral-400">Max {{ room.max_occupancy }} persona</span>
                                <Link :href="`/book?room_type=${room.id}`" class="text-body-sm text-accent-600 font-medium hover:text-accent-700 no-underline">
                                    Rezervo →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-20 bg-primary-950">
            <div class="max-w-3xl mx-auto px-4 text-center">
                <h2 class="text-3xl sm:text-4xl font-bold text-white">Gati per pushime?</h2>
                <p class="text-body text-neutral-400 mt-3">Rezervoni direkt dhe perfitoni cmimin me te mire</p>
                <Link href="/book" class="inline-block mt-6 px-8 py-3.5 rounded-lg bg-accent-600 text-white text-body font-medium hover:bg-accent-700 transition-all shadow-lg no-underline">
                    Rezervo Tani
                </Link>
            </div>
        </section>
    </WebsiteLayout>
</template>
