<script setup>
import PageHeader from '@/Components/UI/PageHeader.vue';
import ToastContainer from '@/Components/UI/ToastContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AboutTab from './Tabs/AboutTab.vue';
import AdministrationTab from './Tabs/AdministrationTab.vue';
import AiTab from './Tabs/AiTab.vue';
import AmenitiesTab from './Tabs/AmenitiesTab.vue';
import CurrenciesTab from './Tabs/CurrenciesTab.vue';
import FinancialTab from './Tabs/FinancialTab.vue';
import FloorsTab from './Tabs/FloorsTab.vue';
import HotelTab from './Tabs/HotelTab.vue';
import HousekeepingTab from './Tabs/HousekeepingTab.vue';
import MarketRatesTab from './Tabs/MarketRatesTab.vue';
import MenuTab from './Tabs/MenuTab.vue';
import PricingProgramsTab from './Tabs/PricingProgramsTab.vue';
import RoomTypesTab from './Tabs/RoomTypesTab.vue';
import WebsiteTab from './Tabs/WebsiteTab.vue';

const props = defineProps({
    settings: Object,
    checklistDefaults: { type: Object, default: () => ({}) },
    roomTypes: Array,
    menuCategories: Array,
    inventoryItems: { type: Array, default: () => [] },
    inventoryWarehouses: { type: Array, default: () => [] },
    floors: Array,
    amenities: Array,
});

const { locale } = useI18n();
const toasts = ref(null);
const activeTab = ref('hotel');
const modules = computed(() => usePage().props.modules || {});

const allTabs = [
    { id: 'hotel', labelSq: 'Të dhënat e hotelit', labelEn: 'Hotel information', group: 'hotel' },
    { id: 'website', labelSq: 'Faqja Web', labelEn: 'Website', group: 'hotel' },
    { id: 'about', labelSq: 'Rreth Nesh', labelEn: 'About page', group: 'hotel' },
    { id: 'room-types', labelSq: 'Tipet e dhomave', labelEn: 'Room types', group: 'hotel' },
    { id: 'amenities', labelSq: 'Pajisjet', labelEn: 'Amenities', group: 'hotel' },
    { id: 'floors', labelSq: 'Katet', labelEn: 'Floors', group: 'hotel' },
    { id: 'menu', labelSq: 'Menuja POS', labelEn: 'POS menu', group: 'operations', module: 'pos' },
    { id: 'housekeeping', labelSq: 'Housekeeping', labelEn: 'Housekeeping', group: 'operations', module: 'housekeeping' },
    { id: 'financial', labelSq: 'Financa', labelEn: 'Finance', group: 'operations' },
    { id: 'currencies', labelSq: 'Monedhat', labelEn: 'Currencies', group: 'operations', module: 'finance' },
    { id: 'pricing-programs', labelSq: 'Çmimet & OTA', labelEn: 'Pricing & OTA', group: 'operations' },
    { id: 'market-rates', labelSq: 'Çmimet e tregut', labelEn: 'Market rates', group: 'operations' },
    { id: 'ai', labelSq: 'Asistenti AI', labelEn: 'AI assistant', group: 'system' },
    { id: 'administration', labelSq: 'Administrimi', labelEn: 'Administration', group: 'system' },
];

const tabs = computed(() => allTabs
    .filter((tab) => !tab.module || modules.value[tab.module] === true)
    .map((tab) => ({ ...tab, label: locale.value === 'sq' ? tab.labelSq : tab.labelEn })));

const groups = computed(() => [
    { id: 'hotel', label: locale.value === 'sq' ? 'Hoteli' : 'Hotel' },
    { id: 'operations', label: locale.value === 'sq' ? 'Operacionet' : 'Operations' },
    { id: 'system', label: locale.value === 'sq' ? 'Sistemi' : 'System' },
].map((group) => ({ ...group, tabs: tabs.value.filter((tab) => tab.group === group.id) })));
</script>

<template>
    <AppLayout>
        <PageHeader
            :title="$t('accountCenter.settingsTitle')"
            :breadcrumbs="[{ label: 'Dashboard', href: '/dashboard' }, { label: $t('accountCenter.settingsTitle') }]"
        />
        <p class="mt-1 text-body-sm text-neutral-500">{{ $t('accountCenter.settingsSubtitle') }}</p>

        <div class="mt-6 flex flex-col gap-6 lg:flex-row">
            <aside class="shrink-0 lg:w-64">
                <nav class="rounded-xl border border-neutral-200 bg-white p-2 shadow-card">
                    <div v-for="group in groups" :key="group.id" :class="group.id !== 'hotel' && 'mt-3'">
                        <p class="px-3 pb-1 pt-2 text-[10px] font-bold uppercase tracking-[0.12em] text-neutral-400">{{ group.label }}</p>
                        <button
                            v-for="tab in group.tabs"
                            :key="tab.id"
                            class="block w-full rounded-lg px-3 py-2.5 text-left text-body-sm transition-colors duration-150"
                            :class="activeTab === tab.id ? 'bg-accent-50 font-semibold text-accent-700' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900'"
                            @click="activeTab = tab.id"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </nav>
            </aside>

            <div class="min-w-0 flex-1">
                <HotelTab v-if="activeTab === 'hotel'" :settings="settings.hotel || {}" :toasts="toasts" />
                <WebsiteTab v-else-if="activeTab === 'website'" :settings="settings.hotel || {}" :toasts="toasts" />
                <AboutTab v-else-if="activeTab === 'about'" :settings="settings.about || {}" :toasts="toasts" />
                <RoomTypesTab v-else-if="activeTab === 'room-types'" :room-types="roomTypes" :amenities="amenities" :toasts="toasts" />
                <AmenitiesTab v-else-if="activeTab === 'amenities'" :amenities="amenities" :toasts="toasts" />
                <FloorsTab v-else-if="activeTab === 'floors'" :floors="floors" :toasts="toasts" />
                <MenuTab v-else-if="activeTab === 'menu'" :categories="menuCategories" :inventory-items="inventoryItems" :warehouses="inventoryWarehouses" :inventory-enabled="modules.finance === true" :toasts="toasts" />
                <HousekeepingTab v-else-if="activeTab === 'housekeeping'" :settings="settings.housekeeping || {}" :checklist-defaults="checklistDefaults" :toasts="toasts" />
                <FinancialTab v-else-if="activeTab === 'financial'" :settings="settings.financial || {}" :toasts="toasts" />
                <CurrenciesTab v-else-if="activeTab === 'currencies'" :settings="settings.currencies || {}" :toasts="toasts" />
                <PricingProgramsTab v-else-if="activeTab === 'pricing-programs'" :settings="settings.pricing_programs || {}" :financial="settings.financial || {}" :toasts="toasts" />
                <MarketRatesTab v-else-if="activeTab === 'market-rates'" :settings="settings.market_rates || {}" :toasts="toasts" />
                <AiTab v-else-if="activeTab === 'ai'" :settings="settings.ai || {}" :toasts="toasts" />
                <AdministrationTab v-else-if="activeTab === 'administration'" />
            </div>
        </div>

        <ToastContainer ref="toasts" />
    </AppLayout>
</template>
