<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { translate } from '@/i18n';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import ToastContainer from '@/Components/UI/ToastContainer.vue';
import { countryName } from '@/countries';
import {
    AlertCircle,
    BedDouble,
    CalendarDays,
    CheckCircle2,
    Clock3,
    FileScan,
    FileText,
    Mail,
    Pencil,
    Phone,
    ShieldCheck,
    Sparkles,
    UploadCloud,
    UserRound,
    Wallet,
    X,
} from 'lucide-vue-next';

const props = defineProps({
    initialGuest: { type: Object, required: true },
    stats: { type: Object, required: true },
    stays: { type: Array, default: () => [] },
    documents: { type: Array, default: () => [] },
    activities: { type: Array, default: () => [] },
    demo: { type: Boolean, default: false },
});

const guest = reactive({ ...props.initialGuest });
const localDocuments = ref(props.documents.map((document) => ({ ...document })));
const toasts = ref(null);
const showAiReview = ref(false);
const analyzing = ref(false);
const selectedFields = ref(['first_name', 'last_name', 'nationality', 'date_of_birth', 'document_type', 'document_number']);
let analysisTimer = null;

const extracted = {
    first_name: 'Marjus',
    last_name: 'Bushi',
    nationality: 'ALB',
    date_of_birth: '1992-04-18',
    document_type: 'passport',
    document_number: 'BA1234567',
};

const aiRows = computed(() => [
    { key: 'first_name', current: guest.first_name, extracted: extracted.first_name, confidence: 99 },
    { key: 'last_name', current: guest.last_name, extracted: extracted.last_name, confidence: 99 },
    { key: 'nationality', current: guest.nationality ? countryName(guest.nationality) : '—', extracted: countryName(extracted.nationality), confidence: 98 },
    { key: 'date_of_birth', current: guest.date_of_birth || '—', extracted: extracted.date_of_birth, confidence: 97 },
    { key: 'document_type', current: guest.document_type ? translate(`admin.guestProfileDesign.documentTypes.${guest.document_type}`) : '—', extracted: translate(`admin.guestProfileDesign.documentTypes.${extracted.document_type}`), confidence: 99 },
    { key: 'document_number', current: guest.document_number || '—', extracted: extracted.document_number, confidence: 96 },
]);

const completedIdentityFields = computed(() => [
    guest.first_name,
    guest.last_name,
    guest.email,
    guest.phone,
    guest.nationality,
    guest.date_of_birth,
    guest.document_number,
].filter(Boolean).length);
const profileCompletion = computed(() => Math.round((completedIdentityFields.value / 7) * 100));
const initials = computed(() => `${guest.first_name?.[0] || ''}${guest.last_name?.[0] || ''}`.toUpperCase());

function startAiReview() {
    analyzing.value = true;
    clearTimeout(analysisTimer);
    analysisTimer = setTimeout(() => {
        analyzing.value = false;
        showAiReview.value = true;
    }, 700);
}

function onMockFile(event) {
    if (!event.target.files?.length) return;
    localDocuments.value = [{
        id: `mock-${Date.now()}`,
        type: 'passport',
        name: event.target.files[0].name,
        size: '1.8 MB',
        status: 'ready',
        uploaded_at: new Date().toISOString().slice(0, 10),
    }, ...localDocuments.value];
    startAiReview();
    event.target.value = '';
}

function toggleField(key) {
    selectedFields.value = selectedFields.value.includes(key)
        ? selectedFields.value.filter((field) => field !== key)
        : [...selectedFields.value, key];
}

function applyAiData() {
    for (const key of selectedFields.value) guest[key] = extracted[key];
    localDocuments.value = localDocuments.value.map((document, index) => index === 0
        ? { ...document, status: 'verified' }
        : document);
    showAiReview.value = false;
    toasts.value?.success(translate('admin.guestProfileDesign.appliedDemo'));
}

onBeforeUnmount(() => clearTimeout(analysisTimer));
</script>

<template>
    <div class="pb-10">
        <PageHeader
            :title="`${guest.first_name} ${guest.last_name}`"
            :breadcrumbs="[
                { label: $t('admin.guestProfileDesign.dashboard'), href: '/dashboard' },
                { label: $t('admin.guestProfileDesign.guests'), href: route('guests.index') },
                { label: $t('admin.guestProfileDesign.preview') },
            ]"
        >
            <template #actions>
                <Badge v-if="demo" variant="warning">{{ $t('admin.guestProfileDesign.mockData') }}</Badge>
                <Link :href="route('guests.index')" class="no-underline"><Button variant="outline">{{ $t('admin.guestProfileDesign.back') }}</Button></Link>
                <Button variant="outline" @click="toasts?.success($t('admin.guestProfileDesign.mockAction'))"><Pencil class="h-4 w-4" />{{ $t('admin.guestProfileDesign.editProfile') }}</Button>
            </template>
        </PageHeader>

        <section class="mt-5 overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-card">
            <div class="flex flex-col gap-5 bg-gradient-to-r from-primary-950 via-primary-900 to-accent-900 px-5 py-5 text-white lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-white/10 text-h2 font-extrabold ring-1 ring-white/20">{{ initials }}</div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="truncate text-h2 text-white">{{ guest.first_name }} {{ guest.last_name }}</h1>
                            <Badge variant="success" dot>{{ $t('admin.guestProfileDesign.returningGuest') }}</Badge>
                            <Badge :variant="profileCompletion === 100 ? 'success' : 'warning'">{{ profileCompletion }}% {{ $t('admin.guestProfileDesign.complete') }}</Badge>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-body-sm text-primary-100">
                            <span class="inline-flex items-center gap-1.5"><Mail class="h-4 w-4" />{{ guest.email }}</span>
                            <span class="inline-flex items-center gap-1.5"><Phone class="h-4 w-4" />{{ guest.phone }}</span>
                            <span class="inline-flex items-center gap-1.5"><UserRound class="h-4 w-4" />ID #{{ guest.id }}</span>
                        </div>
                    </div>
                </div>
                <div class="w-full max-w-xs rounded-xl bg-white/10 p-3 ring-1 ring-white/15">
                    <div class="flex items-center justify-between text-tiny"><span>{{ $t('admin.guestProfileDesign.identityCompletion') }}</span><strong>{{ profileCompletion }}%</strong></div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/15"><div class="h-full rounded-full bg-success-400 transition-all" :style="{ width: `${profileCompletion}%` }" /></div>
                    <p class="mt-2 text-[11px] text-primary-100">{{ $t('admin.guestProfileDesign.completionHelp') }}</p>
                </div>
            </div>
        </section>

        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <Card><div class="flex items-center justify-between"><div><p class="text-tiny text-neutral-500">{{ $t('admin.guestProfileDesign.stays') }}</p><p class="mt-1 text-h3 text-primary-900">{{ stats.total_stays }}</p></div><span class="grid h-10 w-10 place-items-center rounded-xl bg-accent-50 text-accent-700"><BedDouble class="h-5 w-5" /></span></div></Card>
            <Card><div class="flex items-center justify-between"><div><p class="text-tiny text-neutral-500">{{ $t('admin.guestProfileDesign.nights') }}</p><p class="mt-1 text-h3 text-primary-900">{{ stats.total_nights }}</p></div><span class="grid h-10 w-10 place-items-center rounded-xl bg-info-50 text-info-700"><CalendarDays class="h-5 w-5" /></span></div></Card>
            <Card><div class="flex items-center justify-between"><div><p class="text-tiny text-neutral-500">{{ $t('admin.guestProfileDesign.value') }}</p><p class="mt-1 text-h3 text-primary-900">€{{ stats.lifetime_spend }}</p></div><span class="grid h-10 w-10 place-items-center rounded-xl bg-success-50 text-success-700"><Wallet class="h-5 w-5" /></span></div></Card>
            <Card><div class="flex items-center justify-between"><div><p class="text-tiny text-neutral-500">{{ $t('admin.guestProfileDesign.lastStay') }}</p><p class="mt-1 text-body-sm font-extrabold text-primary-900">{{ stats.last_stay }}</p></div><span class="grid h-10 w-10 place-items-center rounded-xl bg-warning-50 text-warning-700"><Clock3 class="h-5 w-5" /></span></div></Card>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-12">
            <div class="space-y-4 xl:col-span-4">
                <Card :padding="false">
                    <template #header><div class="flex items-center justify-between"><div><h2 class="text-body font-bold text-primary-900">{{ $t('admin.guestProfileDesign.identity') }}</h2><p class="text-tiny text-neutral-400">{{ $t('admin.guestProfileDesign.identitySubtitle') }}</p></div><ShieldCheck class="h-5 w-5 text-accent-600" /></div></template>
                    <dl class="divide-y divide-neutral-100 px-5">
                        <div class="flex justify-between gap-4 py-3"><dt class="text-tiny text-neutral-500">{{ $t('admin.guestProfileDesign.fullName') }}</dt><dd class="text-right text-body-sm font-semibold text-primary-900">{{ guest.first_name }} {{ guest.last_name }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-tiny text-neutral-500">{{ $t('admin.guestProfileDesign.nationality') }}</dt><dd class="text-right text-body-sm font-semibold text-primary-900">{{ guest.nationality ? countryName(guest.nationality) : '—' }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-tiny text-neutral-500">{{ $t('admin.guestProfileDesign.birthDate') }}</dt><dd class="text-right text-body-sm font-semibold" :class="guest.date_of_birth ? 'text-primary-900' : 'text-warning-700'">{{ guest.date_of_birth || $t('admin.guestProfileDesign.missing') }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-tiny text-neutral-500">{{ $t('admin.guestProfileDesign.document') }}</dt><dd class="text-right text-body-sm font-semibold" :class="guest.document_number ? 'text-primary-900' : 'text-warning-700'">{{ guest.document_number || $t('admin.guestProfileDesign.missing') }}</dd></div>
                    </dl>
                </Card>

                <Card :padding="false">
                    <template #header><div class="flex items-center justify-between"><div><h2 class="text-body font-bold text-primary-900">{{ $t('admin.guestProfileDesign.documents') }}</h2><p class="text-tiny text-neutral-400">{{ $t('admin.guestProfileDesign.documentsSubtitle') }}</p></div><FileText class="h-5 w-5 text-neutral-400" /></div></template>
                    <div class="space-y-3 p-4">
                        <article v-for="document in localDocuments" :key="document.id" class="rounded-xl border border-neutral-200 p-3">
                            <div class="flex items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-info-50 text-info-700"><FileScan class="h-4 w-4" /></span><div class="min-w-0 flex-1"><p class="truncate text-body-sm font-bold text-primary-900">{{ document.name }}</p><p class="mt-0.5 text-[10px] text-neutral-400">{{ document.size }} · {{ document.uploaded_at }}</p></div><Badge :variant="document.status === 'verified' ? 'success' : 'warning'" dot>{{ document.status === 'verified' ? $t('admin.guestProfileDesign.verified') : $t('admin.guestProfileDesign.readyReview') }}</Badge></div>
                            <Button v-if="document.status !== 'verified'" class="mt-3 w-full justify-center" size="sm" :loading="analyzing" @click="startAiReview"><Sparkles class="h-4 w-4" />{{ $t('admin.guestProfileDesign.reviewWithAi') }}</Button>
                        </article>
                        <label class="flex cursor-pointer flex-col items-center rounded-xl border border-dashed border-accent-300 bg-accent-50/40 px-4 py-5 text-center hover:bg-accent-50">
                            <UploadCloud class="h-6 w-6 text-accent-700" /><span class="mt-2 text-body-sm font-bold text-accent-800">{{ $t('admin.guestProfileDesign.uploadDocument') }}</span><span class="mt-1 text-[10px] text-neutral-500">JPG, PNG, PDF · max 25MB</span><input type="file" class="sr-only" accept="image/*,.pdf" @change="onMockFile" />
                        </label>
                    </div>
                </Card>
            </div>

            <div class="space-y-4 xl:col-span-8">
                <Card :padding="false">
                    <template #header><div><h2 class="text-body font-bold text-primary-900">{{ $t('admin.guestProfileDesign.stayHistory') }}</h2><p class="text-tiny text-neutral-400">{{ $t('admin.guestProfileDesign.staySubtitle') }}</p></div></template>
                    <div class="overflow-x-auto"><table class="min-w-full"><thead class="bg-neutral-50 text-left text-tiny uppercase tracking-wide text-neutral-500"><tr><th class="px-5 py-3">{{ $t('admin.guestProfileDesign.room') }}</th><th class="px-5 py-3">{{ $t('admin.guestProfileDesign.period') }}</th><th class="px-5 py-3">{{ $t('admin.guestProfileDesign.status') }}</th><th class="px-5 py-3 text-right">{{ $t('admin.guestProfileDesign.total') }}</th></tr></thead><tbody class="divide-y divide-neutral-100"><tr v-for="stay in stays" :key="stay.id" class="hover:bg-neutral-50"><td class="px-5 py-3"><p class="text-body-sm font-bold text-primary-900">{{ stay.room }}</p><p class="text-[10px] text-neutral-400">{{ stay.room_type }}</p></td><td class="whitespace-nowrap px-5 py-3 text-body-sm text-neutral-600">{{ stay.check_in }} → {{ stay.check_out }}</td><td class="px-5 py-3"><Badge :variant="stay.status === 'checked_out' ? 'neutral' : stay.status === 'confirmed' ? 'info' : 'error'" dot>{{ $t(`admin.guestProfileDesign.statuses.${stay.status}`) }}</Badge></td><td class="px-5 py-3 text-right text-body-sm font-bold text-primary-900">€{{ stay.total }}</td></tr></tbody></table></div>
                </Card>

                <Card :padding="false">
                    <template #header><div><h2 class="text-body font-bold text-primary-900">{{ $t('admin.guestProfileDesign.activity') }}</h2><p class="text-tiny text-neutral-400">{{ $t('admin.guestProfileDesign.activitySubtitle') }}</p></div></template>
                    <div class="divide-y divide-neutral-100"><article v-for="activity in activities" :key="activity.id" class="flex gap-3 px-5 py-3"><span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-full" :class="activity.tone === 'success' ? 'bg-success-50 text-success-700' : 'bg-info-50 text-info-700'"><CheckCircle2 class="h-4 w-4" /></span><div><p class="text-body-sm font-semibold text-primary-900">{{ $t(`admin.guestProfileDesign.activities.${activity.title_key}`) }}</p><p class="mt-0.5 text-tiny text-neutral-400">{{ activity.meta }}</p></div></article></div>
                </Card>
            </div>
        </div>

        <Teleport to="body">
            <button v-if="showAiReview" type="button" class="fixed inset-0 z-40 cursor-default bg-primary-950/25 backdrop-blur-[1px]" :aria-label="$t('admin.guestProfileDesign.close')" @click="showAiReview = false" />
            <aside v-if="showAiReview" class="fixed inset-y-0 right-0 z-50 flex w-full max-w-2xl flex-col border-l border-neutral-200 bg-neutral-50 shadow-2xl" role="dialog" aria-modal="true" :aria-label="$t('admin.guestProfileDesign.aiReview')">
                <header class="border-b border-neutral-200 bg-white px-5 py-4"><div class="flex items-start justify-between gap-4"><div class="flex items-start gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-accent-100 text-accent-700"><Sparkles class="h-5 w-5" /></span><div><h2 class="text-h3 text-primary-900">{{ $t('admin.guestProfileDesign.aiReview') }}</h2><p class="mt-1 text-body-sm text-neutral-500">{{ $t('admin.guestProfileDesign.aiReviewSubtitle') }}</p></div></div><button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-neutral-400 hover:bg-neutral-100" @click="showAiReview = false"><X class="h-5 w-5" /></button></div></header>
                <div class="min-h-0 flex-1 overflow-y-auto p-5">
                    <div class="flex items-start gap-3 rounded-xl border border-info-100 bg-info-50 p-3 text-body-sm text-info-800"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" /><p>{{ $t('admin.guestProfileDesign.aiSafety') }}</p></div>
                    <div class="mt-4 overflow-hidden rounded-xl border border-neutral-200 bg-white"><div class="grid grid-cols-[36px_1fr_1fr_70px] gap-3 border-b border-neutral-200 bg-neutral-50 px-4 py-2 text-[10px] font-bold uppercase tracking-wide text-neutral-500"><span /><span>{{ $t('admin.guestProfileDesign.current') }}</span><span>{{ $t('admin.guestProfileDesign.extracted') }}</span><span>{{ $t('admin.guestProfileDesign.confidence') }}</span></div><label v-for="row in aiRows" :key="row.key" class="grid cursor-pointer grid-cols-[36px_1fr_1fr_70px] gap-3 border-b border-neutral-100 px-4 py-3 last:border-0 hover:bg-neutral-50"><input type="checkbox" class="mt-1 rounded border-neutral-300 text-accent-600 focus:ring-accent-500" :checked="selectedFields.includes(row.key)" @change="toggleField(row.key)" /><div><p class="text-[10px] font-bold uppercase text-neutral-400">{{ $t(`admin.guestProfileDesign.fields.${row.key}`) }}</p><p class="mt-1 break-words text-body-sm text-neutral-600">{{ row.current }}</p></div><div><p class="text-[10px] font-bold uppercase text-accent-600">AI</p><p class="mt-1 break-words text-body-sm font-bold text-primary-900">{{ row.extracted }}</p></div><Badge :variant="row.confidence >= 98 ? 'success' : 'warning'">{{ row.confidence }}%</Badge></label></div>
                </div>
                <footer class="flex flex-col-reverse gap-2 border-t border-neutral-200 bg-white p-4 sm:flex-row sm:justify-end"><Button variant="outline" @click="showAiReview = false">{{ $t('admin.guestProfileDesign.cancel') }}</Button><Button :disabled="!selectedFields.length" @click="applyAiData"><CheckCircle2 class="h-4 w-4" />{{ $t('admin.guestProfileDesign.applySelected', { count: selectedFields.length }) }}</Button></footer>
            </aside>
        </Teleport>

        <ToastContainer ref="toasts" />
    </div>
</template>
