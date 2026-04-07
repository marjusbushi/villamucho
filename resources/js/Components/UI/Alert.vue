<script setup>
import { ref } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'info',
        validator: (v) => ['success', 'warning', 'error', 'info'].includes(v),
    },
    title: {
        type: String,
        default: null,
    },
    dismissible: {
        type: Boolean,
        default: false,
    },
});

const dismissed = ref(false);

const variantConfig = {
    success: {
        bg: 'bg-success-50 border-success-200',
        icon: 'text-success-500',
        title: 'text-success-800',
        text: 'text-success-700',
        iconPath: 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z',
    },
    warning: {
        bg: 'bg-warning-50 border-warning-200',
        icon: 'text-warning-500',
        title: 'text-warning-800',
        text: 'text-warning-700',
        iconPath: 'M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z',
    },
    error: {
        bg: 'bg-error-50 border-error-200',
        icon: 'text-error-500',
        title: 'text-error-800',
        text: 'text-error-700',
        iconPath: 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z',
    },
    info: {
        bg: 'bg-info-50 border-info-200',
        icon: 'text-info-500',
        title: 'text-info-800',
        text: 'text-info-700',
        iconPath: 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z',
    },
};

const config = variantConfig[props.variant];
</script>

<template>
    <div
        v-if="!dismissed"
        :class="['flex gap-3 rounded-lg border p-4', config.bg]"
        role="alert"
    >
        <svg :class="['h-5 w-5 shrink-0 mt-0.5', config.icon]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" :d="config.iconPath" clip-rule="evenodd" />
        </svg>

        <div class="flex-1 min-w-0">
            <p v-if="title" :class="['text-label', config.title]">{{ title }}</p>
            <div :class="['text-body-sm', config.text, title && 'mt-1']">
                <slot />
            </div>
        </div>

        <button
            v-if="dismissible"
            :class="['shrink-0 rounded-md p-0.5 transition-colors duration-150', config.text, 'hover:opacity-70']"
            @click="dismissed = true"
        >
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
            </svg>
        </button>
    </div>
</template>
