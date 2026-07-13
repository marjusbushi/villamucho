<script setup>
import { translate } from '@/i18n';
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';

// NOTE: we do NOT import the npm SDK here. POK's docs are explicit: the drop-in card form
// (renderForm) must come from the CDN build (window.PokPayment) or the React component — the
// npm `import` path is only the low-level encryptCard(). Bundling the npm build through Vite
// broke the SDK (status-0 aborted requests → GENERAL_ERROR). The CDN build is self-contained.
const POK_CDN = 'https://static.pokpay.io/public/dist/pokpayments/pok-payment.js';

const props = defineProps({
    orderId: String,
    env: { type: String, default: 'staging' },
    amount: { type: Number, default: 0 },
    currency: { type: String, default: '€' },
    guestName: { type: String, default: null },
    confirmUrl: String,
    payUrl: { type: String, default: null },
    initialState: { type: Object, default: () => ({}) }, // pre-fill email/name/country/phone from the booking
    roomName: { type: String, default: null },
    nights: { type: Number, default: 0 },
    adults: { type: Number, default: 0 },
    children: { type: Number, default: 0 },
    holdExpiresAt: { type: String, default: null }, // ISO — when the 30-min room hold ends
    openForPayment: { type: Boolean, default: true },
});

const error = ref('');
const diag = ref('');
const confirming = ref(false);
const started = ref(false);
const sdkLoading = ref(false);
const errorBox = ref(null);
const flashError = computed(() => usePage().props.flash?.error);

function money(v) { const n = Number(v) || 0; return n % 1 === 0 ? String(n) : n.toFixed(2); }
function note(m) { diag.value += (diag.value ? '\n' : '') + m; try { console.log('[POK]', m); } catch (e) {} }

// ── 30-minute room-hold countdown ──
const now = ref(Date.now());
let tick = null;
const holdRemainingMs = computed(() => (props.holdExpiresAt ? new Date(props.holdExpiresAt).getTime() - now.value : null));
const holdExpired = computed(() => holdRemainingMs.value !== null && holdRemainingMs.value <= 0);
const holdUrgent = computed(() => holdRemainingMs.value !== null && holdRemainingMs.value > 0 && holdRemainingMs.value < 5 * 60 * 1000);
const holdClock = computed(() => {
    if (holdRemainingMs.value === null || holdRemainingMs.value <= 0) return '0:00';
    const s = Math.floor(holdRemainingMs.value / 1000);
    return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
});

function loadPokSdk() {
    return new Promise((resolve, reject) => {
        if (window.PokPayment) return resolve(window.PokPayment);
        const s = document.createElement('script');
        s.src = POK_CDN;
        s.async = true;
        s.onload = () => (window.PokPayment ? resolve(window.PokPayment) : reject(new Error('PokPayment global missing after load')));
        s.onerror = () => reject(new Error(translate('admin.generated.k_775ad0f66d84')));
        document.head.appendChild(s);
    });
}

// When POK's form appears inside #pok-form, clear the loading note and hand FOCUS to it
// (first input, or the iframe — the only cross-origin-legal handoff). 12s safety timeout.
function focusPokFormWhenReady() {
    const host = document.getElementById('pok-form');
    if (!host) return;
    const obs = new MutationObserver(() => {
        const el = host.querySelector('input, select, iframe, button');
        if (!el) return;
        obs.disconnect();
        sdkLoading.value = false;
        el.focus({ preventScroll: true });
        host.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
    obs.observe(host, { childList: true, subtree: true });
    setTimeout(() => { obs.disconnect(); sdkLoading.value = false; }, 12000);
}

async function startPayment() {
    if (started.value || !props.orderId || holdExpired.value) return;
    started.value = true;
    sdkLoading.value = true;
    error.value = '';
    try {
        const Pok = await loadPokSdk();
        note('PokPayment.renderForm(' + props.orderId + ', env=' + props.env + ')');
        focusPokFormWhenReady();
        Pok.renderForm(
            'pok-form',
            props.orderId,
            () => {
                confirming.value = true;
                router.post(props.confirmUrl, {}, {
                    onError: () => { confirming.value = false; error.value = translate('admin.generated.k_00c09206cd2d'); },
                });
            },
            (e) => {
                note('SDK onError: ' + JSON.stringify(e ?? {}));
                // Silent safety net: if the card form fails, send the guest to POK's hosted page
                // (the reservation stays held) rather than leaving them stuck.
                if (props.payUrl) { window.location.href = props.payUrl; return; }
                error.value = e?.message || translate('admin.generated.k_3fa37dcaeaff');
                started.value = false; // bring the retry button back — never a dead end
            },
            // Pre-fill identity fields so the guest enters ONLY card number / expiry / CVC.
            { env: props.env, locale: 'al', initialState: { ...props.initialState } },
        );
    } catch (ex) {
        note('startPayment threw: ' + (ex?.message || ex));
        error.value = ex?.message || "Forma s'u ngarkua.";
        started.value = false;
        sdkLoading.value = false;
    }
}

// Any error (local or flashed by the server) renders ABOVE the card form where a phone
// guest isn't looking — move focus + view to it.
watch([error, flashError], ([e, f]) => {
    if (e || f) nextTick(() => {
        errorBox.value?.focus({ preventScroll: true });
        errorBox.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
});

onMounted(() => {
    // Diagnostics (staging) — surface any silent SDK failure without DevTools.
    window.addEventListener('error', (ev) => note('JS error: ' + (ev.message || ev.error?.message || ev.error)));
    try {
        const OrigXHR = window.XMLHttpRequest;
        window.XMLHttpRequest = function () {
            const xhr = new OrigXHR();
            let u = '';
            const open = xhr.open;
            xhr.open = function (m, url, ...rest) { u = m + ' ' + url; return open.call(xhr, m, url, ...rest); };
            xhr.addEventListener('loadend', () => { if (xhr.status === 0 || xhr.status >= 400) note('XHR ' + xhr.status + ' ' + u); });
            return xhr;
        };
        window.XMLHttpRequest.prototype = OrigXHR.prototype;
    } catch (e) { note('instrument failed: ' + e.message); }

    if (props.holdExpiresAt) tick = setInterval(() => { now.value = Date.now(); }, 1000);

    // The guest already committed on the previous step ("Vazhdo te pagesa") — start the
    // card form immediately; the button below only reappears as a retry after a failure.
    if (props.openForPayment && props.orderId && !holdExpired.value) startPayment();
});

onUnmounted(() => { if (tick) clearInterval(tick); });
</script>

<template>
    <Head :title="$t('admin.generated.k_9ae4255a7958')" />
    <WebsiteLayout>
        <div class="max-w-xl mx-auto px-5 py-16 sm:py-20">
            <p class="text-eyebrow text-ionian mb-3">{{ $t('admin.generated.k_063103ecd5d8') }}</p>
            <h1 class="font-serif text-display-sm text-ink">{{ $t('admin.generated.k_6aeaf1b56910') }}</h1>
            <p class="text-driftwood mt-2 mb-8 lead">
                <template v-if="guestName">{{ guestName }}{{ $t('admin.generated.k_5c4d67b1c0db') }}</template><template v-else>{{ $t('admin.generated.k_e3a040b84398') }}</template>{{ $t('admin.generated.k_fec5be500f0c') }} </p>

            <!-- summary -->
            <div class="rounded-2xl border border-limestone bg-bone/60 p-5 mb-3">
                <div class="flex items-center justify-between text-ink/70 text-body-sm">
                    <span>{{ roomName || $t('admin.generated.k_56dfd63697e3') }}</span>
                    <span>{{ nights }} {{ $t('admin.generated.k_b26dd1a3ecab') }}<template v-if="adults"> · {{ adults }} {{ $t('admin.generated.k_4b3616423937') }}</template><template v-if="children">, {{ children }} {{ $t('admin.generated.k_e97cb6c00455') }}</template></span>
                </div>
                <div class="flex items-baseline justify-between mt-3 pt-3 border-t border-limestone">
                    <span class="text-ink font-medium">{{ $t('admin.generated.k_4eb893f1c8e6') }}</span>
                    <span class="font-serif text-3xl text-brass">{{ currency }}{{ money(amount) }}</span>
                </div>
            </div>

            <!-- room-hold countdown -->
            <p
                v-if="holdExpiresAt && !holdExpired && openForPayment"
                role="status"
                :class="['text-center text-body-sm mb-6 tabular-nums', holdUrgent ? 'text-error-600 font-medium' : 'text-driftwood']"
            >
{{ $t('admin.generated.k_ab6a0585f9cb') }} {{ holdClock }} {{ $t('admin.generated.k_b75f2e453e36') }} </p>

            <div
                v-if="error || flashError"
                ref="errorBox"
                role="alert"
                tabindex="-1"
                class="mb-5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-body-sm px-4 py-3 focus:outline-none"
            >
                {{ error || flashError }}
            </div>

            <!-- hold expired → the release cron frees the room; don't offer a dead payment -->
            <div v-if="holdExpired" class="rounded-xl bg-limestone/40 border border-limestone text-ink/80 text-body-sm px-4 py-6 text-center">
                <p>{{ $t('admin.generated.k_a019b0f5d840') }}</p>
                <Link href="/book" class="btn-reserve inline-block mt-4">{{ $t('admin.generated.k_b400e5ca8025') }}</Link>
            </div>

            <div v-else-if="!openForPayment" class="rounded-xl bg-limestone/40 border border-limestone text-ink/80 text-body-sm px-4 py-6 text-center">
                <p>{{ $t('admin.generated.k_19c40fdd4f7a') }}</p>
                <button type="button" class="mt-3 text-ionian underline text-body-sm" @click="router.reload()">{{ $t('admin.generated.k_e6d7988a4128') }}</button>
            </div>

            <template v-else>
                <!-- retry (the form auto-starts; this shows only after a failure) -->
                <div v-if="!started" class="text-center">
                    <button type="button" @click="startPayment"
                        class="rounded-xl bg-ionian text-white font-medium px-7 py-3.5 hover:bg-ionian-dark">
                        {{ error ? $t('admin.generated.k_26515f673d03') : $t('admin.generated.k_837effc7f915') }}
                    </button>
                </div>

                <p v-if="sdkLoading" role="status" class="text-center text-ink/70 text-body-sm py-6">{{ $t('admin.generated.k_6204d45003f2') }}</p>

                <!-- POK card form (CDN build) mounts here; hidden while the server confirms so a
                     nervous guest can't re-tap POK's pay button mid-confirmation -->
                <div v-show="started && !confirming" id="pok-form" tabindex="-1" :aria-label="$t('admin.generated.k_d73a2595b37d')" class="min-h-[220px] outline-none"></div>

                <p v-if="confirming" role="status" aria-live="polite" class="text-center text-driftwood text-body-sm mt-5">
                    <span class="inline-block h-4 w-4 mr-1.5 align-[-2px] rounded-full border-2 border-ionian border-t-transparent animate-spin" aria-hidden="true"></span>
{{ $t('admin.generated.k_4c2c6bfb97ba') }} </p>

                <p class="text-center text-tiny text-driftwood mt-8 leading-relaxed">
{{ $t('admin.generated.k_7ae537141bc8') }} </p>

                <p v-if="env === 'staging'" class="text-center text-tiny text-driftwood mt-3 leading-relaxed">
{{ $t('admin.generated.k_560f42d51bb9') }} <b class="text-ink">4242 4242 4242 4242</b>,<br>
{{ $t('admin.generated.k_a656d10acb69') }} </p>

                <pre v-if="env === 'staging' && diag" class="mt-6 whitespace-pre-wrap break-words rounded-lg bg-ink/5 border border-limestone text-ink/60 text-[11px] leading-relaxed p-3">{{ diag }}</pre>
            </template>
        </div>
    </WebsiteLayout>
</template>
