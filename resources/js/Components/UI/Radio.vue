<script setup>
const emit = defineEmits(['update:modelValue']);

defineProps({
    modelValue: {
        type: [String, Number, Boolean],
        default: null,
    },
    value: {
        required: true,
    },
    label: {
        type: String,
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    name: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <label class="inline-flex items-center gap-2" :class="disabled && 'opacity-60 cursor-not-allowed'">
        <input
            type="radio"
            :checked="modelValue === value"
            :value="value"
            :name="name"
            :disabled="disabled"
            class="h-4 w-4 border-neutral-300 text-accent-600 focus:ring-2 focus:ring-accent-500/40 focus:ring-offset-0 transition-colors duration-150"
            :class="disabled && 'cursor-not-allowed'"
            @change="emit('update:modelValue', value)"
        />
        <span v-if="label" class="text-body-sm text-neutral-700 select-none">{{ label }}</span>
    </label>
</template>
