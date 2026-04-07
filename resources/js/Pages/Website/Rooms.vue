<script setup>
import { Head, Link } from '@inertiajs/vue3';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';

defineProps({ roomTypes: Array });

const amenityIcons = {
    'WiFi': '📶', 'TV': '📺', 'TV 55"': '📺', 'Aire kondicionuar': '❄️', 'Banjo private': '🚿',
    'Banjo luksoze': '🛁', 'Ballkon': '🌅', 'Minibar': '🍹', 'Pamje nga deti': '🌊',
    'Makineri kafeje': '☕', 'Shtrat shtese': '🛏️',
};
</script>

<template>
    <Head title="Dhomat — Villa Mucho" />
    <WebsiteLayout>
        <section class="py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h1 class="text-h1 text-primary-900">Dhomat Tona</h1>
                    <p class="text-body text-neutral-500 mt-2 max-w-lg mx-auto">Cdo dhome eshte projektuar per komoditetin dhe relaksimin tuaj</p>
                </div>

                <div class="space-y-12">
                    <div v-for="room in roomTypes" :key="room.id" class="bg-white rounded-2xl border border-neutral-100 overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="grid grid-cols-1 lg:grid-cols-2">
                            <!-- Image -->
                            <div class="h-64 lg:h-auto bg-gradient-to-br from-accent-50 to-neutral-100 flex items-center justify-center">
                                <span class="text-7xl">🏨</span>
                            </div>
                            <!-- Info -->
                            <div class="p-6 lg:p-8 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-start justify-between mb-3">
                                        <h2 class="text-h2 text-primary-900">{{ room.name }}</h2>
                                        <div class="text-right">
                                            <p class="text-h3 text-accent-600">€{{ room.base_price }}</p>
                                            <p class="text-small text-neutral-400">per nate</p>
                                        </div>
                                    </div>
                                    <p class="text-body text-neutral-600 mb-4">{{ room.description }}</p>

                                    <div class="flex items-center gap-4 mb-4 text-body-sm text-neutral-500">
                                        <span>👥 Max {{ room.max_occupancy }} persona</span>
                                        <span>🏠 {{ room.rooms_count }} dhoma</span>
                                        <span class="text-accent-600 font-medium">{{ room.available_count }} te lira</span>
                                    </div>

                                    <!-- Amenities -->
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="a in room.amenities || []" :key="a" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-neutral-50 border border-neutral-100 text-body-sm text-neutral-700">
                                            {{ amenityIcons[a] || '✓' }} {{ a }}
                                        </span>
                                    </div>
                                </div>

                                <Link :href="`/book?room_type=${room.id}`" class="mt-6 block w-full px-6 py-3 rounded-lg bg-accent-600 text-white text-body-sm font-medium text-center hover:bg-accent-700 transition-colors no-underline">
                                    Rezervo {{ room.name }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </WebsiteLayout>
</template>
