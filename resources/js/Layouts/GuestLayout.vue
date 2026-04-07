<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const hotelName = usePage().props.settings?.hotel_name || 'Hotel';
const initial = hotelName.charAt(0);
</script>

<template>
    <div class="flex min-h-screen">
        <!-- Left panel — branding -->
        <div class="hidden lg:flex lg:w-1/2 bg-primary-950 items-center justify-center relative overflow-hidden">
            <div class="relative z-10 px-16 text-center">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-xl bg-accent-600 mb-8">
                    <span class="text-white font-bold text-h2">{{ initial }}</span>
                </div>
                <h1 class="text-h1 text-white mb-3">{{ hotelName }}</h1>
                <p class="text-body text-neutral-400 max-w-sm mx-auto">
                    Sistemi i plote per menaxhimin e hotelit tuaj — rezervime, dhoma, housekeeping dhe POS ne nje vend.
                </p>
            </div>
            <div class="absolute inset-0 bg-gradient-to-br from-accent-900/20 via-transparent to-primary-900/40" />
        </div>

        <!-- Right panel — form -->
        <div class="flex flex-1 flex-col items-center justify-center px-6 py-12 bg-neutral-50">
            <div class="lg:hidden mb-8 text-center">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-lg bg-accent-600 mb-3">
                    <span class="text-white font-bold text-h4">{{ initial }}</span>
                </div>
                <h2 class="text-h3 text-primary-900">{{ hotelName }}</h2>
            </div>

            <div class="w-full max-w-sm">
                <slot />
            </div>
        </div>
    </div>
</template>
