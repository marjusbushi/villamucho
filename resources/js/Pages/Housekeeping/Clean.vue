<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, Link, Head } from '@inertiajs/vue3';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import Modal from '@/Components/UI/Modal.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import FormGroup from '@/Components/UI/FormGroup.vue';

const props = defineProps({
    task: Object,
});

const typeBadge = {
    checkout_clean: { variant: 'error', label: 'Check-out' },
    stayover_clean: { variant: 'info', label: 'Ditor' },
    deep_clean: { variant: 'dark', label: 'Thellesi' },
    inspection: { variant: 'neutral', label: 'Inspektim' },
};

// --- Checklist state (optimistic local copy of the server snapshot) ---
const items = ref((props.task.checklist || []).map((i) => ({ ...i })));
const doneCount = computed(() => items.value.filter((i) => i.done).length);
const total = computed(() => items.value.length);
const allDone = computed(() => (total.value === 0 ? true : doneCount.value === total.value));
const pct = computed(() => (total.value === 0 ? 100 : Math.round((doneCount.value / total.value) * 100)));

let saveTimer = null;
function persist() {
    router.patch(
        route('housekeeping.checklist', props.task.id),
        { items: items.value.map((i) => ({ label: i.label, done: i.done })) },
        { preserveScroll: true, preserveState: true },
    );
}
function toggle(idx) {
    items.value[idx].done = !items.value[idx].done;
    clearTimeout(saveTimer);
    saveTimer = setTimeout(persist, 400);
}

// --- Live timer from the SERVER started_at (survives refresh / reopen) ---
const startedMs = props.task.started_at ? Date.parse(props.task.started_at) : null;
const now = ref(Date.now());
let ticker = null;
onMounted(() => {
    ticker = setInterval(() => (now.value = Date.now()), 1000);
});
onUnmounted(() => {
    clearInterval(ticker);
    clearTimeout(saveTimer);
});
const elapsed = computed(() => {
    if (!startedMs) return '00:00';
    let s = Math.max(0, Math.floor((now.value - startedMs) / 1000));
    const h = Math.floor(s / 3600);
    s -= h * 3600;
    const m = Math.floor(s / 60);
    const sec = s - m * 60;
    const pad = (n) => String(n).padStart(2, '0');
    return h > 0 ? `${h}:${pad(m)}:${pad(sec)}` : `${pad(m)}:${pad(sec)}`;
});

// --- Finish: flush checklist, then complete, then back to the board ---
const finishing = ref(false);
function finish() {
    if (!allDone.value) return;
    clearTimeout(saveTimer);
    finishing.value = true;
    router.patch(
        route('housekeeping.checklist', props.task.id),
        { items: items.value.map((i) => ({ label: i.label, done: i.done })) },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                router.patch(
                    route('housekeeping.status', props.task.id),
                    { status: 'completed' },
                    {
                        onSuccess: () => router.visit(route('housekeeping.index')),
                        onFinish: () => (finishing.value = false),
                    },
                );
            },
            onError: () => (finishing.value = false),
        },
    );
}

// --- Report a problem (reuse the board's issue flow) ---
const showIssue = ref(false);
const issueText = ref('');
const setMaintenance = ref(false);
function submitIssue() {
    router.post(
        route('housekeeping.issue', props.task.id),
        { issue_reported: issueText.value, set_maintenance: setMaintenance.value },
        { preserveScroll: true, onSuccess: () => (showIssue.value = false) },
    );
}
</script>

<template>
    <Head :title="`Pastrim — Dhoma ${task.room?.room_number ?? ''}`" />

    <div class="min-h-screen bg-neutral-50 flex flex-col">
        <!-- Header -->
        <header class="sticky top-0 z-10 bg-white border-b border-neutral-200 px-4 py-3">
            <div class="flex items-center gap-3">
                <Link
                    :href="route('housekeeping.index')"
                    class="h-9 w-9 -ml-1 flex items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 shrink-0"
                    aria-label="Prapa"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div class="min-w-0">
                    <p class="text-h4 text-primary-900 leading-none">Dhoma {{ task.room?.room_number }}</p>
                    <p class="text-small text-neutral-500 mt-1 truncate">
                        {{ task.room?.room_type }}<span v-if="task.room?.floor"> · Kati {{ task.room?.floor }}</span>
                    </p>
                </div>
                <Badge :variant="typeBadge[task.type]?.variant" size="sm" class="ml-auto shrink-0">{{ typeBadge[task.type]?.label }}</Badge>
            </div>
        </header>

        <!-- Timer band -->
        <div class="bg-primary-900 text-white px-4 py-6 text-center">
            <p class="text-tiny uppercase tracking-widest text-primary-300">Koha e pastrimit</p>
            <p class="text-5xl font-semibold tabular-nums leading-none mt-2">{{ elapsed }}</p>
        </div>

        <!-- Progress -->
        <div class="px-4 pt-4">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-label text-neutral-700">Lista e punëve</span>
                <span class="text-small font-medium text-neutral-500">{{ doneCount }}/{{ total }}</span>
            </div>
            <div class="h-2 rounded-full bg-neutral-200 overflow-hidden">
                <div class="h-full bg-success-500 transition-all duration-300" :style="{ width: pct + '%' }" />
            </div>
        </div>

        <!-- Checklist -->
        <div class="flex-1 px-4 py-4 space-y-2.5">
            <button
                v-for="(item, idx) in items"
                :key="idx"
                type="button"
                class="w-full flex items-center gap-3 rounded-xl border px-4 py-4 text-left transition-colors active:bg-neutral-50"
                :class="item.done ? 'border-success-200 bg-success-50' : 'border-neutral-200 bg-white'"
                @click="toggle(idx)"
            >
                <span
                    class="h-7 w-7 rounded-lg border-2 flex items-center justify-center shrink-0 transition-colors"
                    :class="item.done ? 'bg-success-500 border-success-500 text-white' : 'border-neutral-300 bg-white'"
                >
                    <svg v-if="item.done" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
                <span class="text-body" :class="item.done ? 'text-neutral-400 line-through' : 'text-primary-900'">{{ item.label }}</span>
            </button>

            <p v-if="!total" class="text-center text-body-sm text-neutral-500 py-10">
                Kjo detyrë s'ka listë pune — mund ta përfundosh direkt.
            </p>

            <p v-if="task.issue_reported" class="text-small text-error-600 pt-2">⚠ {{ task.issue_reported }}</p>
        </div>

        <!-- Sticky footer -->
        <footer class="sticky bottom-0 bg-white border-t border-neutral-200 px-4 py-3 flex items-center gap-3">
            <button type="button" class="text-body-sm font-medium text-error-600 px-2 py-2" @click="showIssue = true">
                Problem
            </button>
            <Button
                class="ml-auto"
                size="lg"
                variant="success"
                :disabled="!allDone"
                :loading="finishing"
                @click="finish"
            >
                Perfundo<span v-if="!allDone" class="opacity-90"> · {{ doneCount }}/{{ total }}</span>
            </Button>
        </footer>

        <!-- Issue Report Modal -->
        <Modal :show="showIssue" title="Raporto problem" max-width="md" @close="showIssue = false">
            <div class="space-y-4">
                <FormGroup label="Pershkrimi i problemit" required>
                    <Textarea v-model="issueText" placeholder="Pershkruaj problemin e gjetur..." :rows="3" />
                </FormGroup>
                <label class="flex items-center gap-2 text-body-sm text-neutral-700">
                    <input type="checkbox" v-model="setMaintenance" class="h-4 w-4 rounded border-neutral-300 text-accent-600" />
                    Vendos dhomen ne mirembajtje
                </label>
            </div>
            <template #footer>
                <Button variant="outline" @click="showIssue = false">Anulo</Button>
                <Button variant="danger" :disabled="!issueText" @click="submitIssue">Raporto</Button>
            </template>
        </Modal>
    </div>
</template>
