<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
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
    openForPayment: { type: Boolean, default: true },
});

const error = ref('');
const diag = ref('');
const confirming = ref(false);
const started = ref(false);

function money(v) { const n = Number(v) || 0; return n % 1 === 0 ? String(n) : n.toFixed(2); }
function note(m) { diag.value += (diag.value ? '\n' : '') + m; try { console.log('[POK]', m); } catch (e) {} }

function loadPokSdk() {
    return new Promise((resolve, reject) => {
        if (window.PokPayment) return resolve(window.PokPayment);
        const s = document.createElement('script');
        s.src = POK_CDN;
        s.async = true;
        s.onload = () => (window.PokPayment ? resolve(window.PokPayment) : reject(new Error('PokPayment global missing after load')));
        s.onerror = () => reject(new Error('Nuk u ngarkua dot SDK-ja e POK-ut nga CDN.'));
        document.head.appendChild(s);
    });
}

onMounted(() => {
    // Diagnostics only (staging) — confirm the CDN build no longer aborts (no "XHR 0").
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
});

async function startPayment() {
    if (started.value || !props.orderId) return;
    started.value = true;
    try {
        const Pok = await loadPokSdk();
        note('PokPayment.renderForm(' + props.orderId + ', env=' + props.env + ')');
        Pok.renderForm(
            'pok-form',
            props.orderId,
            () => {
                confirming.value = true;
                router.post(props.confirmUrl, {}, {
                    onError: () => { confirming.value = false; error.value = "Pagesa u krye, por s'u konfirmua ende. Prit pak sekonda dhe rifresko."; },
                });
            },
            (e) => {
                note('SDK onError: ' + JSON.stringify(e ?? {}));
                // Silent safety net: if the card form fails, send the guest to POK's hosted page
                // (the reservation stays held) rather than leaving them stuck.
                if (props.payUrl) { window.location.href = props.payUrl; return; }
                error.value = e?.message || 'Pagesa dështoi.';
            },
            // Pre-fill identity fields so the guest enters ONLY card number / expiry / CVC.
            { env: props.env, locale: 'al', initialState: { ...props.initialState } },
        );
    } catch (ex) {
        note('startPayment threw: ' + (ex?.message || ex));
        error.value = ex?.message || "Forma s'u ngarkua.";
        started.value = false;
    }
}
</script>

<template>
    <WebsiteLayout>
        <div class="max-w-xl mx-auto px-5 py-16 sm:py-20">
            <p class="text-eyebrow text-ionian mb-3">Hapi i fundit</p>
            <h1 class="font-serif text-display-sm text-ink">Përfundo pagesën</h1>
            <p class="text-driftwood mt-2 mb-8 lead">
                <template v-if="guestName">{{ guestName }}, r</template><template v-else>R</template>ezervimi mbahet për ty derisa të paguash. Pagesa është e sigurt përmes POK.
            </p>

            <div class="rounded-2xl border border-limestone bg-bone/60 p-5 mb-6">
                <div class="flex items-center justify-between text-ink/70 text-body-sm">
                    <span>{{ roomName || 'Dhoma' }}</span>
                    <span>{{ nights }} net</span>
                </div>
                <div class="flex items-baseline justify-between mt-3 pt-3 border-t border-limestone">
                    <span class="text-ink font-medium">Total për të paguar</span>
                    <span class="font-serif text-3xl text-brass">{{ currency }}{{ money(amount) }}</span>
                </div>
            </div>

            <div v-if="error" class="mb-5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-body-sm px-4 py-3">{{ error }}</div>

            <div v-if="!openForPayment" class="rounded-xl bg-limestone/40 border border-limestone text-ink/80 text-body-sm px-4 py-6 text-center">
                Po konfirmojmë pagesën tënde… Nëse e ke paguar, rifresko këtë faqe pas pak sekondash.
            </div>

            <template v-if="openForPayment">
                <div v-if="!started" class="text-center">
                    <button type="button" @click="startPayment"
                        class="rounded-xl bg-ionian text-white font-medium px-7 py-3.5 hover:bg-ionian-dark">
                        Paguaj me kartë
                    </button>
                </div>

                <!-- POK card form (CDN build) mounts here -->
                <div v-show="started" id="pok-form" class="min-h-[220px]"></div>

                <p v-if="confirming" class="text-center text-driftwood text-body-sm mt-5">Po konfirmohet pagesa…</p>

                <p v-if="env === 'staging'" class="text-center text-tiny text-driftwood mt-8 leading-relaxed">
                    Modaliteti TEST — përdor kartën <b class="text-ink">4242 4242 4242 4242</b>,<br>
                    datë skadence në të ardhmen, çfarëdo CVV 3-shifror.
                </p>

                <pre v-if="env === 'staging' && diag" class="mt-6 whitespace-pre-wrap break-words rounded-lg bg-ink/5 border border-limestone text-ink/60 text-[11px] leading-relaxed p-3">{{ diag }}</pre>
            </template>
        </div>
    </WebsiteLayout>
</template>
