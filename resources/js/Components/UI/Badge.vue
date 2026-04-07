<script setup>
defineProps({
    variant: {
        type: String,
        default: 'neutral',
        validator: (v) => ['success', 'warning', 'error', 'info', 'neutral', 'accent', 'dark'].includes(v),
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md'].includes(v),
    },
    dot: {
        type: Boolean,
        default: false,
    },
});

const variantClasses = {
    success: 'bg-success-50 text-success-700 ring-1 ring-success-200/60',
    warning: 'bg-warning-50 text-warning-700 ring-1 ring-warning-200/60',
    error: 'bg-error-50 text-error-700 ring-1 ring-error-200/60',
    info: 'bg-info-50 text-info-700 ring-1 ring-info-200/60',
    neutral: 'bg-neutral-100 text-neutral-600 ring-1 ring-neutral-200/60',
    accent: 'bg-accent-50 text-accent-700 ring-1 ring-accent-200/60',
    dark: 'bg-primary-900 text-primary-100 ring-1 ring-primary-700/40',
};

const dotClasses = {
    success: 'bg-success-500',
    warning: 'bg-warning-500',
    error: 'bg-error-500',
    info: 'bg-info-500',
    neutral: 'bg-neutral-400',
    accent: 'bg-accent-500',
    dark: 'bg-primary-300',
};

const sizeClasses = {
    sm: 'px-1.5 py-0.5 text-tiny',
    md: 'px-2 py-0.5 text-tiny',
};
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 rounded-md font-medium tracking-wide uppercase',
            variantClasses[variant],
            sizeClasses[size],
        ]"
    >
        <span v-if="dot" :class="['h-1.5 w-1.5 rounded-full', dotClasses[variant]]" />
        <slot />
    </span>
</template>
