<script setup>
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    shift: { type: Object, default: null },
    canOpen: { type: Boolean, default: false },
    canClose: { type: Boolean, default: false },
});

defineEmits(['open', 'close']);

function money(v) {
    return `€${Number(v ?? 0).toFixed(2)}`;
}
</script>

<template>
    <!-- OPEN: green strip with live drawer chips -->
    <div
        v-if="shift"
        class="rounded-xl border border-success-200 bg-gradient-to-r from-success-50 to-white px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-3"
    >
        <div class="flex items-center gap-2 shrink-0">
            <span class="h-2.5 w-2.5 rounded-full bg-success-500 animate-pulse"></span>
            <span class="text-body-sm font-semibold text-success-800">
                🟢 Turn i hapur nga {{ shift.user_name }}
            </span>
            <span class="text-small text-neutral-500">⏱️ {{ shift.opened_at }}</span>
        </div>

        <!-- live money chips -->
        <div class="flex flex-wrap items-center gap-2 sm:ml-auto">
            <span class="px-2.5 py-1 rounded-lg bg-success-100 text-success-800 text-small font-medium">💶 Kesh {{ money(shift.cash_sales) }}</span>
            <span class="px-2.5 py-1 rounded-lg bg-info-100 text-info-800 text-small font-medium">💳 Kartë {{ money(shift.card_sales) }}</span>
            <span class="px-2.5 py-1 rounded-lg bg-warning-100 text-warning-800 text-small font-medium">🏨 Në folio {{ money(shift.room_charge_sales) }}</span>
            <span class="px-2.5 py-1 rounded-lg bg-neutral-100 text-neutral-600 text-small font-medium">🧾 {{ shift.completed_orders }} porosi</span>
            <Button v-if="canClose" variant="outline" size="sm" class="!border-error-300 !text-error-600 hover:!bg-error-50" @click="$emit('close')">
                Mbyll Turn
            </Button>
        </div>
    </div>

    <!-- CLOSED: amber call-to-action -->
    <div
        v-else
        class="rounded-xl border border-warning-200 bg-gradient-to-r from-warning-50 to-white px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-3"
    >
        <div class="flex items-center gap-2">
            <span class="h-2.5 w-2.5 rounded-full bg-error-400"></span>
            <span class="text-body-sm font-semibold text-warning-900">🔴 Turni i mbyllur</span>
            <span class="text-small text-neutral-500">— hap turnin për të filluar punën</span>
        </div>
        <div class="sm:ml-auto">
            <Button v-if="canOpen" variant="primary" size="sm" @click="$emit('open')">🔓 Hap Turn</Button>
            <span v-else class="text-small text-neutral-400">S'ke leje të hapësh turn.</span>
        </div>
    </div>
</template>
