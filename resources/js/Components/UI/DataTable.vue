<script setup>
import { translate } from '@/i18n';
import { ref, computed } from 'vue';

const props = defineProps({
    columns: {
        type: Array,
        required: true,
        // Each column: { key: string, label: string, sortable?: boolean, width?: string, align?: 'left'|'center'|'right' }
    },
    data: {
        type: Array,
        required: true,
    },
    perPage: {
        type: Number,
        default: 10,
    },
    emptyText: {
        type: String,
        default: translate('admin.generated.k_6963d5f9628a'),
    },
    clickableRows: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['row-click', 'sort']);

const currentPage = ref(1);
const sortKey = ref(null);
const sortDirection = ref('asc');

const totalPages = computed(() => Math.ceil(props.data.length / props.perPage));

const sortedData = computed(() => {
    if (!sortKey.value) return props.data;

    return [...props.data].sort((a, b) => {
        const aVal = a[sortKey.value];
        const bVal = b[sortKey.value];

        if (aVal == null) return 1;
        if (bVal == null) return -1;

        const comparison = typeof aVal === 'string'
            ? aVal.localeCompare(bVal)
            : aVal - bVal;

        return sortDirection.value === 'asc' ? comparison : -comparison;
    });
});

const paginatedData = computed(() => {
    const start = (currentPage.value - 1) * props.perPage;
    return sortedData.value.slice(start, start + props.perPage);
});

const visiblePages = computed(() => {
    const pages = [];
    const total = totalPages.value;
    const current = currentPage.value;

    if (total <= 7) {
        for (let i = 1; i <= total; i++) pages.push(i);
    } else {
        pages.push(1);
        if (current > 3) pages.push('...');
        for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
            pages.push(i);
        }
        if (current < total - 2) pages.push('...');
        pages.push(total);
    }

    return pages;
});

function toggleSort(key) {
    if (sortKey.value === key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDirection.value = 'asc';
    }
    currentPage.value = 1;
    emit('sort', { key: sortKey.value, direction: sortDirection.value });
}

function goToPage(page) {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
}
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white">
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200">
                <thead class="bg-neutral-50">
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            :style="col.width ? { width: col.width } : {}"
                            :class="[
                                'px-4 py-3 text-label text-neutral-600 whitespace-nowrap',
                                col.align === 'center' ? 'text-center' : col.align === 'right' ? 'text-right' : 'text-left',
                                col.sortable && 'cursor-pointer select-none hover:text-neutral-900',
                            ]"
                            @click="col.sortable && toggleSort(col.key)"
                        >
                            <span class="inline-flex items-center gap-1">
                                {{ col.label }}
                                <template v-if="col.sortable">
                                    <svg
                                        v-if="sortKey === col.key"
                                        class="h-4 w-4 text-accent-600"
                                        :class="sortDirection === 'desc' && 'rotate-180'"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3z" clip-rule="evenodd" />
                                    </svg>
                                    <svg
                                        v-else
                                        class="h-4 w-4 text-neutral-300"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </template>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    <tr
                        v-for="(row, index) in paginatedData"
                        :key="index"
                        :class="[
                            'transition-colors duration-100',
                            clickableRows && 'cursor-pointer hover:bg-accent-50',
                            !clickableRows && 'hover:bg-neutral-50',
                        ]"
                        @click="clickableRows && emit('row-click', row)"
                    >
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            :class="[
                                'px-4 py-3 text-body-sm text-neutral-700 whitespace-nowrap',
                                col.align === 'center' ? 'text-center' : col.align === 'right' ? 'text-right' : 'text-left',
                            ]"
                        >
                            <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                                {{ row[col.key] }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty state -->
        <div v-if="data.length === 0" class="px-6 py-12 text-center">
            <p class="text-body-sm text-neutral-500">{{ emptyText }}</p>
        </div>

        <!-- Pagination -->
        <div
            v-if="totalPages > 1"
            class="flex items-center justify-between border-t border-neutral-200 bg-neutral-50 px-4 py-3"
        >
            <p class="text-small text-neutral-500">
                {{ (currentPage - 1) * perPage + 1 }}–{{ Math.min(currentPage * perPage, data.length) }}
{{ $t('admin.generated.k_5c3c6feccd8c') }} {{ data.length }}
            </p>
            <nav class="flex items-center gap-1">
                <button
                    :disabled="currentPage === 1"
                    class="rounded-md px-2 py-1 text-small text-neutral-600 hover:bg-neutral-200 disabled:opacity-40 disabled:cursor-not-allowed"
                    @click="goToPage(currentPage - 1)"
                >
                    &larr;
                </button>
                <template v-for="page in visiblePages" :key="page">
                    <span v-if="page === '...'" class="px-1 text-neutral-400">...</span>
                    <button
                        v-else
                        :class="[
                            'rounded-md px-2.5 py-1 text-small font-medium',
                            page === currentPage
                                ? 'bg-accent-600 text-white'
                                : 'text-neutral-600 hover:bg-neutral-200',
                        ]"
                        @click="goToPage(page)"
                    >
                        {{ page }}
                    </button>
                </template>
                <button
                    :disabled="currentPage === totalPages"
                    class="rounded-md px-2 py-1 text-small text-neutral-600 hover:bg-neutral-200 disabled:opacity-40 disabled:cursor-not-allowed"
                    @click="goToPage(currentPage + 1)"
                >
                    &rarr;
                </button>
            </nav>
        </div>
    </div>
</template>
