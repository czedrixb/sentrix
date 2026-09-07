<script setup>
import { onMounted, ref, watch } from 'vue';
import { admin } from '@/api';
import { formatMoney } from '@/support/format';
import PaginationBar from '@/components/PaginationBar.vue';
import RevenueChart from '@/components/RevenueChart.vue';

/**
 * Dashboard.
 *
 * Three requests, not one: the counters, the revenue series and the low-stock
 * list each move independently, so paging the stock table or changing the chart
 * range does not refetch every figure on the screen.
 */
const data = ref(null);
const loading = ref(true);

// --- Revenue chart -------------------------------------------------------
const ranges = [
    { key: '30d', label: '30 days' },
    { key: '12w', label: '12 weeks' },
    { key: '12m', label: '12 months' },
];

const range = ref('12w');
const series = ref(null);
const seriesLoading = ref(true);

async function loadSeries() {
    seriesLoading.value = true;

    try {
        series.value = await admin.revenueSeries(range.value);
    } finally {
        seriesLoading.value = false;
    }
}

// --- Low stock -----------------------------------------------------------
const lowStock = ref([]);
const lowStockMeta = ref(null);
const lowStockLoading = ref(true);
const search = ref('');
const page = ref(1);

let searchTimer = null;

async function loadLowStock() {
    lowStockLoading.value = true;

    try {
        const response = await admin.lowStock({
            q: search.value || undefined,
            page: page.value,
        });

        lowStock.value = response.data;
        lowStockMeta.value = response.meta;
    } finally {
        lowStockLoading.value = false;
    }
}

/** Debounced, so typing a SKU is one request rather than one per keystroke. */
watch(search, () => {
    window.clearTimeout(searchTimer);

    searchTimer = window.setTimeout(() => {
        page.value = 1;
        loadLowStock();
    }, 300);
});

watch(page, loadLowStock);
watch(range, loadSeries);

onMounted(async () => {
    loadSeries();
    loadLowStock();

    try {
        data.value = await admin.dashboard();
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div>
        <header class="mb-6">
            <h1 class="font-heading text-2xl font-semibold text-ink-900">Dashboard</h1>
            <p v-if="data" class="text-sm text-ink-400">{{ data.scope }}</p>
        </header>

        <div v-if="loading" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="n in 8" :key="n" class="h-28 animate-pulse rounded-2xl bg-white" />
        </div>

        <template v-else-if="data">
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article
                    v-for="(bucket, key) in data.orders_by_status"
                    :key="key"
                    class="card p-5"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">{{ bucket.label }}</p>
                    <p class="mt-1 font-display text-3xl text-ink-900">{{ bucket.total }}</p>
                </article>
            </section>

            <section class="mt-4 grid gap-4 sm:grid-cols-3">
                <article class="card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">Unpaid orders</p>
                    <p class="mt-1 font-display text-3xl text-brand-600">{{ data.orders_unpaid }}</p>
                </article>
                <article class="card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">Revenue this month</p>
                    <p class="mt-1 font-display text-3xl text-ink-900">{{ formatMoney(data.revenue_this_month) }}</p>
                </article>
                <article class="card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">Enquiries waiting</p>
                    <p class="mt-1 font-display text-3xl text-ink-900">{{ data.inquiries_unhandled }}</p>
                </article>
            </section>
        </template>

        <!-- Revenue over time ------------------------------------------- -->
        <section class="card mt-6 p-5">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-heading font-semibold text-ink-900">Paid revenue</h2>
                    <p class="text-sm text-ink-400">
                        <template v-if="series">
                            {{ series.label }} &middot;
                            <strong class="text-ink-900">{{ formatMoney(series.total) }}</strong> total
                        </template>
                    </p>
                </div>

                <div class="flex rounded border border-ink-200 p-0.5" role="group" aria-label="Chart range">
                    <button
                        v-for="option in ranges"
                        :key="option.key"
                        type="button"
                        class="rounded px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="range === option.key
                            ? 'bg-brand-600 text-white'
                            : 'text-ink-400 hover:bg-ink-100 hover:text-ink-700'"
                        :aria-pressed="range === option.key"
                        @click="range = option.key"
                    >{{ option.label }}</button>
                </div>
            </div>

            <div v-if="seriesLoading" class="h-[200px] animate-pulse rounded bg-ink-100" />
            <RevenueChart v-else-if="series" :points="series.points" />
        </section>

        <!-- Low stock ---------------------------------------------------- -->
        <section class="card mt-6 p-5">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-heading font-semibold text-ink-900">Low stock</h2>
                    <p v-if="lowStockMeta" class="text-sm text-ink-400">
                        {{ lowStockMeta.total }} product{{ lowStockMeta.total === 1 ? '' : 's' }} at or below threshold
                    </p>
                </div>

                <label class="w-full sm:w-64">
                    <span class="sr-only">Search low stock</span>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Product, SKU or branch"
                        class="field-input"
                    >
                </label>
            </div>

            <div v-if="lowStockLoading" class="space-y-2">
                <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-ink-100" />
            </div>

            <p v-else-if="lowStock.length === 0" class="py-6 text-center text-sm text-ink-400">
                <template v-if="search">Nothing matches &ldquo;{{ search }}&rdquo;.</template>
                <template v-else>Nothing is below its threshold.</template>
            </p>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <!-- nowrap so the two numeric headings scroll rather than
                         wrap into each other on a narrow screen. -->
                    <thead class="text-left text-xs uppercase tracking-wide text-ink-400">
                        <tr>
                            <th class="py-2 pr-4">Product</th>
                            <th class="py-2 pr-4">Branch</th>
                            <th class="whitespace-nowrap py-2 pr-4 text-right">On hand</th>
                            <th class="whitespace-nowrap py-2 text-right">Threshold</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        <tr v-for="row in lowStock" :key="`${row.product_id}-${row.branch_id}`">
                            <td class="py-2 pr-4">
                                {{ row.product_name }}
                                <span class="block text-xs text-ink-400">{{ row.sku }}</span>
                            </td>
                            <td class="py-2 pr-4 text-ink-500">{{ row.branch_name }}</td>
                            <td
                                class="py-2 pr-4 text-right font-semibold tabular-nums"
                                :class="row.quantity === 0 ? 'text-red-600' : 'text-amber-600'"
                            >
                                {{ row.quantity }}
                            </td>
                            <td class="py-2 text-right tabular-nums text-ink-400">{{ row.low_stock_threshold }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationBar :meta="lowStockMeta" @change="page = $event" />
        </section>
    </div>
</template>
