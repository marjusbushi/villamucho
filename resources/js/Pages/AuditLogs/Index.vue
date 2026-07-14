<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import Select from '@/Components/UI/Select.vue';
import DatePicker from '@/Components/UI/DatePicker.vue';
import AuditTimeline from '@/Components/AuditTimeline.vue';
import SettingsSidebar from '@/Components/SettingsSidebar.vue';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
    logs: Object,
    filters: Object,
    embedded: { type: Boolean, default: false },
});
const search = ref(props.filters?.search || '');
const category = ref(props.filters?.category || 'all');
const source = ref(props.filters?.source || 'all');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

const categoryOptions = [
    { value: 'all', label: 'Të gjitha veprimet' },
    { value: 'reservation', label: 'Rezervime' },
    { value: 'guest', label: 'Mysafirë' },
    { value: 'payment', label: 'Pagesa' },
    { value: 'folio', label: 'Folio' },
    { value: 'housekeeping', label: 'Housekeeping' },
    { value: 'pos', label: 'POS' },
    { value: 'user', label: 'Përdorues' },
    { value: 'pricing', label: 'Çmime' },
    { value: 'channex', label: 'Channex' },
];
const sourceOptions = [
    { value: 'all', label: 'Të gjitha burimet' },
    { value: 'staff', label: 'Stafi' },
    { value: 'channex', label: 'Channex / OTA' },
    { value: 'website', label: 'Faqja online' },
    { value: 'import', label: 'Import' },
    { value: 'system', label: 'Sistemi' },
];

function params(extra = {}) {
    const filters = {
        search: search.value || undefined,
        category: category.value,
        source: source.value,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        ...extra,
    };

    if (!props.embedded) return filters;

    return {
        tab: 'history',
        ...Object.fromEntries(Object.entries(filters).map(([key, value]) => [`audit_${key}`, value])),
    };
}
function applyFilters() {
    router.get(props.embedded ? route('settings.index') : route('audit-logs.index'), params(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: props.embedded ? ['auditHistory'] : ['logs', 'filters'],
    });
}
function clearFilters() {
    search.value = ''; category.value = 'all'; source.value = 'all'; dateFrom.value = ''; dateTo.value = '';
    applyFilters();
}
function pageTo(url) {
    if (!url) return;
    const page = new URL(url, window.location.origin).searchParams.get(props.embedded ? 'audit_page' : 'page');
    router.get(props.embedded ? route('settings.index') : route('audit-logs.index'), params({ page }), {
        preserveState: true,
        preserveScroll: true,
        only: props.embedded ? ['auditHistory'] : ['logs', 'filters'],
    });
}

watch(() => props.filters, (value) => {
    search.value = value?.search || '';
    category.value = value?.category || 'all';
    source.value = value?.source || 'all';
    dateFrom.value = value?.date_from || '';
    dateTo.value = value?.date_to || '';
});
</script>

<template>
    <component :is="embedded ? 'div' : AppLayout">
        <PageHeader v-if="!embedded" title="Historia e veprimeve" :breadcrumbs="[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Historia' }]" />
        <p v-if="!embedded" class="mt-1 text-body-sm text-neutral-500">Kontrollo gjurmën e veprimeve dhe ndryshimeve në hotel.</p>

        <div :class="embedded ? '' : 'mt-6 flex flex-col gap-6 lg:flex-row'">
            <SettingsSidebar v-if="!embedded" active-item="history" />

            <div class="min-w-0 flex-1">
        <Card>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6 xl:items-end">
                <div class="xl:col-span-2"><TextInput v-model="search" placeholder="Përdorues, veprim ose ID..." @keyup.enter="applyFilters" /></div>
                <Select v-model="category" :options="categoryOptions" @change="applyFilters" />
                <Select v-model="source" :options="sourceOptions" @change="applyFilters" />
                <DatePicker v-model="dateFrom" placeholder="Nga data" />
                <DatePicker v-model="dateTo" placeholder="Deri më" />
            </div>
            <div class="mt-3 flex gap-2">
                <Button size="sm" variant="primary" @click="applyFilters">Filtro</Button>
                <Button size="sm" variant="ghost" @click="clearFilters">Pastro</Button>
            </div>
        </Card>

        <Card class="mt-6" :padding="false">
            <div class="border-b border-neutral-200 px-5 py-4">
                <h2 class="text-label uppercase tracking-wider text-neutral-600">{{ logs.total }} veprime</h2>
                <p class="mt-0.5 text-tiny text-neutral-400">Ky regjistër është vetëm për lexim dhe nuk mund të ndryshohet.</p>
            </div>
            <AuditTimeline :entries="logs.data" show-ip />
            <div v-if="logs.total" class="flex items-center justify-between border-t border-neutral-200 bg-neutral-50 px-5 py-3">
                <p class="text-small text-neutral-500">{{ logs.from }}–{{ logs.to }} nga {{ logs.total }}</p>
                <div class="flex items-center gap-2">
                    <Button size="sm" variant="outline" :disabled="!logs.prev_page_url" @click="pageTo(logs.prev_page_url)"><ChevronLeft class="h-4 w-4" /> Mbrapa</Button>
                    <span class="text-small text-neutral-500">{{ logs.current_page }} / {{ logs.last_page }}</span>
                    <Button size="sm" variant="outline" :disabled="!logs.next_page_url" @click="pageTo(logs.next_page_url)">Para <ChevronRight class="h-4 w-4" /></Button>
                </div>
            </div>
        </Card>
            </div>
        </div>
    </component>
</template>
