<script setup>
// Dignified branded placeholder shown when a room type has no uploaded photo.
// Replaces the old 🏨 emoji — reads as "photography forthcoming", never AI-slop.
import { ImageOff } from 'lucide-vue-next';

defineProps({
    label: { type: String, default: 'Foto se shpejti' },
});
</script>

<template>
    <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-limestone text-driftwood">
        <ImageOff class="h-7 w-7" :stroke-width="1.25" />
        <span class="eyebrow text-driftwood/80">{{ label }}</span>
    </div>
</template>
