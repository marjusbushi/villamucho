<script setup>
// Room image gallery for the public site (Ionian Calm).
// - Uniform aspect image area that hover-cycles on desktop, swipes on mobile.
// - Subtle dot indicators; click opens a brand-styled fullscreen lightbox.
// - Falls back to the dignified RoomPhotoFallback when a room has no photos.
import { ref, computed, onBeforeUnmount } from 'vue';
import { ChevronLeft, ChevronRight, X, Maximize2 } from 'lucide-vue-next';
import RoomPhotoFallback from './RoomPhotoFallback.vue';

const props = defineProps({
    images: { type: Array, default: () => [] },
    alt: { type: String, default: '' },
    aspect: { type: String, default: 'aspect-[4/3]' },
});

const has = computed(() => Array.isArray(props.images) && props.images.length > 0);
const src = (img) => `/storage/${img.path}`;

const index = ref(0);
let cycleTimer = null;

function clamp(i) {
    const n = props.images.length;
    return n ? (i % n + n) % n : 0;
}
function go(i) { index.value = clamp(i); }
function next() { go(index.value + 1); }
function prev() { go(index.value - 1); }

// Desktop hover-cycle
function startCycle() {
    if (props.images.length < 2 || cycleTimer) return;
    cycleTimer = setInterval(next, 1100);
}
function stopCycle() {
    if (cycleTimer) { clearInterval(cycleTimer); cycleTimer = null; }
    index.value = 0;
}

// Mobile swipe
let touchX = null;
function onTouchStart(e) { touchX = e.changedTouches[0].clientX; }
function onTouchEnd(e) {
    if (touchX === null) return;
    const dx = e.changedTouches[0].clientX - touchX;
    if (Math.abs(dx) > 40) (dx < 0 ? next() : prev());
    touchX = null;
}

// Lightbox
const lightbox = ref(false);
function openLightbox() { if (has.value) { lightbox.value = true; document.addEventListener('keydown', onKey); } }
function closeLightbox() { lightbox.value = false; document.removeEventListener('keydown', onKey); }
function onKey(e) {
    if (e.key === 'Escape') closeLightbox();
    else if (e.key === 'ArrowRight') next();
    else if (e.key === 'ArrowLeft') prev();
}
onBeforeUnmount(() => { stopCycle(); document.removeEventListener('keydown', onKey); });
</script>

<template>
    <!-- No photos → dignified branded fallback -->
    <div v-if="!has" :class="['relative w-full overflow-hidden bg-limestone', aspect]">
        <RoomPhotoFallback />
    </div>

    <!-- Gallery -->
    <div
        v-else
        :class="['group/gal relative w-full overflow-hidden bg-limestone cursor-pointer', aspect]"
        @mouseenter="startCycle"
        @mouseleave="stopCycle"
        @touchstart.passive="onTouchStart"
        @touchend.passive="onTouchEnd"
        @click="openLightbox"
    >
        <img
            v-for="(img, i) in images"
            :key="img.id ?? i"
            :src="src(img)"
            :alt="alt"
            loading="lazy"
            :class="[
                'absolute inset-0 h-full w-full object-cover transition-opacity duration-700',
                i === index ? 'opacity-100' : 'opacity-0',
            ]"
        />

        <!-- view hint -->
        <div class="absolute top-3 right-3 z-10 flex items-center gap-1.5 bg-ink/55 text-bone text-tiny px-2 py-1 opacity-0 group-hover/gal:opacity-100 transition-opacity backdrop-blur-sm">
            <Maximize2 class="h-3.5 w-3.5" :stroke-width="1.5" /> {{ images.length }}
        </div>

        <!-- dots -->
        <div v-if="images.length > 1" class="absolute bottom-3 left-1/2 -translate-x-1/2 z-10 flex items-center gap-1.5">
            <button
                v-for="(img, i) in images"
                :key="'dot' + (img.id ?? i)"
                type="button"
                :aria-label="`Foto ${i + 1}`"
                :class="['h-1.5 rounded-full transition-all', i === index ? 'w-5 bg-bone' : 'w-1.5 bg-bone/55 hover:bg-bone/80']"
                @click.stop="go(i)"
            />
        </div>
    </div>

    <!-- Lightbox -->
    <Teleport to="body">
        <div v-if="lightbox" class="fixed inset-0 z-[100] bg-ink/95 flex items-center justify-center p-4 sm:p-10" @click.self="closeLightbox">
            <button type="button" :aria-label="$t('admin.generated.k_4912b9942b17')" class="absolute top-5 right-5 text-bone/70 hover:text-bone" @click="closeLightbox">
                <X class="h-7 w-7" :stroke-width="1.25" />
            </button>
            <button v-if="images.length > 1" type="button" :aria-label="$t('admin.generated.k_f3caa6eec666')" class="absolute left-4 sm:left-8 text-bone/70 hover:text-bone" @click.stop="prev">
                <ChevronLeft class="h-9 w-9" :stroke-width="1" />
            </button>

            <figure class="max-w-5xl w-full">
                <img :src="src(images[index])" :alt="alt" class="w-full max-h-[80vh] object-contain" />
                <figcaption class="mt-4 text-center eyebrow text-bone/50">{{ alt }} · {{ index + 1 }} / {{ images.length }}</figcaption>
            </figure>

            <button v-if="images.length > 1" type="button" :aria-label="$t('admin.generated.k_826caf136584')" class="absolute right-4 sm:right-8 text-bone/70 hover:text-bone" @click.stop="next">
                <ChevronRight class="h-9 w-9" :stroke-width="1" />
            </button>
        </div>
    </Teleport>
</template>
