<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:modelValue']);

const props = defineProps({
    modelValue: {
        type: [Boolean, Array],
        default: false,
    },
    value: {
        default: null,
    },
    label: {
        type: String,
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const checked = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});
</script>

<template>
    <label class="inline-flex items-center gap-2" :class="disabled && 'opacity-60 cursor-not-allowed'">
        <input
            type="checkbox"
            v-model="checked"
            :value="value"
            :disabled="disabled"
            class="h-4 w-4 rounded border-neutral-300 text-accent-600 focus:ring-2 focus:ring-accent-500/40 focus:ring-offset-0 transition-colors duration-150"
            :class="disabled && 'cursor-not-allowed'"
        />
        <span v-if="label" class="text-body-sm text-neutral-700 select-none">{{ label }}</span>
    </label>
</template>
