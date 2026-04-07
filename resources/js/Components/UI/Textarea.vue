<script setup>
const model = defineModel();

defineProps({
    placeholder: {
        type: String,
        default: null,
    },
    rows: {
        type: [String, Number],
        default: 4,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <textarea
        v-model="model"
        :placeholder="placeholder"
        :rows="rows"
        :disabled="disabled"
        :class="[
            'block w-full rounded-md border px-3 py-2 text-body-sm text-neutral-900 placeholder:text-neutral-400 transition-colors duration-150 resize-y',
            'focus:outline-none focus:ring-2 focus:ring-offset-0',
            disabled && 'bg-neutral-100 cursor-not-allowed opacity-60',
            error
                ? 'border-error-300 focus:border-error-500 focus:ring-error-500/40'
                : 'border-neutral-200 focus:border-accent-500 focus:ring-accent-500/40',
        ]"
    />
</template>
