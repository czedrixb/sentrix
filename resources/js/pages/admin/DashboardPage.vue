<script setup>
import { onMounted, ref } from 'vue';
import { admin } from '@/api';
import { formatMoney } from '@/support/format';

/**
 * Dashboard.
 *
 * One request, one aggregate query behind it. The previous dashboard ran up to
 * thirty-five separate queries inside the view template and counted the
 * resulting collections in PHP.
 */
const data = ref(null);
const loading = ref(true);

onMounted(async () => {
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
            <h1 class="font-heading text-2xl font-bold text-ink-700">Dashboard</h1>
            <p v-if="data" class="text-sm text-slate-500">{{ data.scope }}</p>
        </header>

        <div v-if="loading" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="n in 8" :key="n" class="h-28 animate-pulse rounded-lg bg-white" />
        </div>

        <template v-else-if="data">
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article
                    v-for="(bucket, key) in data.orders_by_status"
                    :key="key"
                    class="card p-5"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ bucket.label }}</p>
                    <p class="mt-1 font-display text-3xl text-ink-700">{{ bucket.total }}</p>
                </article>
            </section>

            <section class="mt-4 grid gap-4 sm:grid-cols-3">
                <article class="card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Unpaid orders</p>
                    <p class="mt-1 font-display text-3xl text-brand-500">{{ data.orders_unpaid }}</p>
                </article>
                <article class="card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Revenue this month</p>
                    <p class="mt-1 font-display text-3xl text-ink-700">{{ formatMoney(data.revenue_this_month) }}</p>
                </article>
                <article class="card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Enquiries waiting</p>
                    <p class="mt-1 font-display text-3xl text-ink-700">{{ data.inquiries_unhandled }}</p>
                </article>
            </section>

            <section class="card mt-6 p-5">
                <h2 class="font-heading font-bold text-ink-700">Low stock</h2>

                <p v-if="data.low_stock.length === 0" class="mt-3 text-sm text-slate-500">
                    Nothing is below its threshold.
                </p>

                <table v-else class="mt-3 w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="py-2">Product</th>
                            <th class="py-2">Branch</th>
                            <th class="py-2 text-right">On hand</th>
                            <th class="py-2 text-right">Threshold</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="row in data.low_stock" :key="`${row.product_id}-${row.branch_id}`">
                            <td class="py-2">
                                {{ row.product_name }}
                                <span class="block text-xs text-slate-400">{{ row.sku }}</span>
                            </td>
                            <td class="py-2 text-slate-600">{{ row.branch_name }}</td>
                            <td class="py-2 text-right font-semibold" :class="row.quantity === 0 ? 'text-red-600' : 'text-amber-600'">
                                {{ row.quantity }}
                            </td>
                            <td class="py-2 text-right text-slate-500">{{ row.low_stock_threshold }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </template>
    </div>
</template>
