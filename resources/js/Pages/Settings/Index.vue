<script setup>
import { translate } from '@/i18n';
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import HotelTab from './Tabs/HotelTab.vue';
import WebsiteTab from './Tabs/WebsiteTab.vue';
import AboutTab from './Tabs/AboutTab.vue';
import RoomTypesTab from './Tabs/RoomTypesTab.vue';
import FloorsTab from './Tabs/FloorsTab.vue';
import AmenitiesTab from './Tabs/AmenitiesTab.vue';
import MenuTab from './Tabs/MenuTab.vue';
import HousekeepingTab from './Tabs/HousekeepingTab.vue';
import FinancialTab from './Tabs/FinancialTab.vue';
import PricingProgramsTab from './Tabs/PricingProgramsTab.vue';
import CurrenciesTab from './Tabs/CurrenciesTab.vue';
import MarketRatesTab from './Tabs/MarketRatesTab.vue';
import AiTab from './Tabs/AiTab.vue';
import ToastContainer from '@/Components/UI/ToastContainer.vue';

const props = defineProps({
    settings: Object,
    checklistDefaults: { type: Object, default: () => ({}) },
    roomTypes: Array,
    menuCategories: Array,
    floors: Array,
    amenities: Array,
});

const toasts = ref(null);
const activeTab = ref('hotel');

const hasFinanceAddon = (usePage().props.tenant?.addons || []).includes('finance');

const tabs = [
    { id: 'hotel', label: translate('admin.generated.k_5d00bebf3a59') },
    { id: 'website', label: translate('admin.generated.k_a52b2023d48c') },
    { id: 'about', label: translate('admin.generated.k_4f476b8b8937') },
    { id: 'room-types', label: translate('admin.generated.k_202a7b47d404') },
    { id: 'amenities', label: translate('admin.generated.k_c18e598a31ab') },
    { id: 'floors', label: translate('admin.generated.k_ae5097d86746') },
    { id: 'menu', label: translate('admin.generated.k_2279df611655') },
    { id: 'housekeeping', label: translate('admin.generated.k_83c5931cf9e9') },
    { id: 'financial', label: translate('admin.generated.k_aa5778855cd0') },
    { id: 'pricing-programs', label: translate('admin.generated.k_a0124fddd9e8') },
    { id: 'market-rates', label: translate('admin.generated.k_c7f91743b945') },
    ...(hasFinanceAddon ? [{ id: 'currencies', label: translate('admin.generated.k_330e93a8a9d5') }] : []),
    { id: 'ai', label: translate('admin.generated.k_19c6878a2e32') },
];
</script>

<template>
    <AppLayout>
        <PageHeader
            :title="$t('admin.generated.k_cb16582d3259')"
            :breadcrumbs="[{ label: $t('admin.generated.k_6bfa9bde2480'), href: '/dashboard' }, { label: $t('admin.generated.k_126a74d0a6b2') }]"
        />

        <div class="mt-6 flex flex-col lg:flex-row gap-6">
            <!-- Sidebar tabs -->
            <aside class="lg:w-56 shrink-0">
                <nav class="space-y-0.5">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        :class="[
                            'block w-full text-left rounded-md px-3 py-2.5 text-body-sm transition-colors duration-150',
                            activeTab === tab.id
                                ? 'bg-accent-50 text-accent-700 font-medium'
                                : 'text-neutral-600 hover:bg-neutral-100',
                        ]"
                        @click="activeTab = tab.id"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </aside>

            <!-- Tab content -->
            <div class="flex-1 min-w-0">
                <HotelTab v-if="activeTab === 'hotel'" :settings="settings.hotel || {}" :toasts="toasts" />
                <WebsiteTab v-else-if="activeTab === 'website'" :settings="settings.hotel || {}" :toasts="toasts" />
                <AboutTab v-else-if="activeTab === 'about'" :settings="settings.about || {}" :toasts="toasts" />
                <RoomTypesTab v-else-if="activeTab === 'room-types'" :room-types="roomTypes" :amenities="amenities" :toasts="toasts" />
                <AmenitiesTab v-else-if="activeTab === 'amenities'" :amenities="amenities" :toasts="toasts" />
                <FloorsTab v-else-if="activeTab === 'floors'" :floors="floors" :toasts="toasts" />
                <MenuTab v-else-if="activeTab === 'menu'" :categories="menuCategories" :toasts="toasts" />
                <HousekeepingTab v-else-if="activeTab === 'housekeeping'" :settings="settings.housekeeping || {}" :checklist-defaults="checklistDefaults" :toasts="toasts" />
                <FinancialTab v-else-if="activeTab === 'financial'" :settings="settings.financial || {}" :toasts="toasts" />
                <PricingProgramsTab v-else-if="activeTab === 'pricing-programs'" :settings="settings.pricing_programs || {}" :financial="settings.financial || {}" :toasts="toasts" />
                <MarketRatesTab v-else-if="activeTab === 'market-rates'" :settings="settings.market_rates || {}" :toasts="toasts" />
                <CurrenciesTab v-else-if="activeTab === 'currencies'" :settings="settings.currencies || {}" :toasts="toasts" />
                <AiTab v-else-if="activeTab === 'ai'" :settings="settings.ai || {}" :toasts="toasts" />
            </div>
        </div>

        <ToastContainer ref="toasts" />
    </AppLayout>
</template>
