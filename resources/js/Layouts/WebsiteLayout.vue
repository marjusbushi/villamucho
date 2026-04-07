<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const mobileMenu = ref(false);
const hotelName = usePage().props.settings?.hotel_name || 'Villa Mucho';

const navLinks = [
    { label: 'Home', href: '/' },
    { label: 'Dhomat', href: '/rooms' },
    { label: 'Rezervo', href: '/book' },
    { label: 'Rreth Nesh', href: '/about' },
    { label: 'Kontakt', href: '/contact' },
];

const page = usePage();
function isActive(href) {
    if (href === '/') return page.url === '/';
    return page.url.startsWith(href);
}
</script>

<template>
    <div class="min-h-screen bg-white">
        <!-- Header -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-sm border-b border-neutral-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center gap-2.5 no-underline">
                        <div class="h-9 w-9 rounded-lg bg-accent-600 flex items-center justify-center">
                            <span class="text-white font-bold text-label">{{ hotelName.charAt(0) }}</span>
                        </div>
                        <span class="text-h4 text-primary-900 hidden sm:block">{{ hotelName }}</span>
                    </Link>

                    <!-- Desktop nav -->
                    <nav class="hidden md:flex items-center gap-1">
                        <Link
                            v-for="link in navLinks"
                            :key="link.href"
                            :href="link.href"
                            :class="[
                                'px-3 py-2 rounded-md text-body-sm font-medium transition-colors duration-150 no-underline',
                                isActive(link.href)
                                    ? 'text-accent-700 bg-accent-50'
                                    : 'text-neutral-600 hover:text-primary-900 hover:bg-neutral-50',
                            ]"
                        >
                            {{ link.label }}
                        </Link>
                        <Link href="/book" class="ml-3 px-5 py-2 rounded-lg bg-accent-600 text-white text-body-sm font-medium hover:bg-accent-700 transition-colors no-underline">
                            Rezervo Tani
                        </Link>
                    </nav>

                    <!-- Mobile hamburger -->
                    <button class="md:hidden p-2 text-neutral-600" @click="mobileMenu = !mobileMenu">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="!mobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile menu -->
                <div v-if="mobileMenu" class="md:hidden pb-4 border-t border-neutral-100 mt-2 pt-3">
                    <Link
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="block px-3 py-2 rounded-md text-body-sm text-neutral-700 hover:bg-neutral-50 no-underline"
                        @click="mobileMenu = false"
                    >
                        {{ link.label }}
                    </Link>
                    <Link href="/book" class="block mt-2 mx-3 px-4 py-2.5 rounded-lg bg-accent-600 text-white text-body-sm font-medium text-center no-underline" @click="mobileMenu = false">
                        Rezervo Tani
                    </Link>
                </div>
            </div>
        </header>

        <!-- Content (offset for fixed header) -->
        <main class="pt-16">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-primary-950 text-neutral-400 mt-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Brand -->
                    <div>
                        <div class="flex items-center gap-2.5 mb-4">
                            <div class="h-8 w-8 rounded-md bg-accent-600 flex items-center justify-center">
                                <span class="text-white font-bold text-small">{{ hotelName.charAt(0) }}</span>
                            </div>
                            <span class="text-label text-neutral-200">{{ hotelName }}</span>
                        </div>
                        <p class="text-body-sm text-neutral-500 max-w-xs">
                            Eksperience unike ne zemren e Shqiperise. Relaksim, natyra, dhe mikpritje e vertete.
                        </p>
                    </div>
                    <!-- Links -->
                    <div>
                        <h4 class="text-label text-neutral-200 mb-3">Navigim</h4>
                        <div class="space-y-2">
                            <Link v-for="link in navLinks" :key="link.href" :href="link.href" class="block text-body-sm text-neutral-500 hover:text-accent-400 no-underline">
                                {{ link.label }}
                            </Link>
                        </div>
                    </div>
                    <!-- Contact -->
                    <div>
                        <h4 class="text-label text-neutral-200 mb-3">Kontakt</h4>
                        <div class="space-y-2 text-body-sm text-neutral-500">
                            <p>{{ usePage().props.settings?.hotel_name || 'Villa Mucho' }}</p>
                            <p>📍 Ksamil, Sarande, Shqiperi</p>
                            <p>📞 +355 69 000 0000</p>
                            <p>✉️ info@villamucho.com</p>
                        </div>
                    </div>
                </div>
                <div class="border-t border-primary-800/50 mt-8 pt-8 text-center text-small text-neutral-600">
                    © {{ new Date().getFullYear() }} {{ hotelName }}. Te gjitha te drejtat e rezervuara.
                </div>
            </div>
        </footer>
    </div>
</template>
