<script setup>
import { onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { orders as ordersApi } from '@/api';
import PaginationBar from '@/components/PaginationBar.vue';
import { formatMoney, formatDateTime } from '@/support/format';

const orders = ref([]);
const meta = ref(null);
const page = ref(1);
const loading = ref(true);

async function load() {
    loading.value = true;

    try {
        const response = await ordersApi.mine({ page: page.value });
        orders.value = response.data;
        meta.value = response.meta;
    } finally {
        loading.value = false;
    }
}

watch(page, load);
onMounted(load);
</script>

<template>
    <div class="mx-auto max-w-5xl px-4 py-10">
        <h1 class="font-display text-4xl text-ink-900">My orders</h1>

        <div v-if="loading" class="mt-8 space-y-3">
            <div v-for="n in 3" :key="n" class="h-24 animate-pulse rounded-2xl bg-ink-100" />
        </div>

        <p v-else-if="orders.length === 0" class="card mt-8 p-12 text-center text-ink-400">
            You have not placed an order yet.
        </p>

        <div v-else class="mt-8 space-y-3">
            <RouterLink
                v-for="order in orders"
                :key="order.order_number"
                :to="{ name: 'order', params: { number: order.order_number }, query: { email: order.customer.email } }"
                class="card flex flex-wrap items-center justify-between gap-4 p-5 transition duration-300 ease-soft hover:shadow-md"
            >
                <div>
                    <p class="font-heading font-semibold text-ink-900">{{ order.order_number }}</p>
                    <p class="text-xs text-ink-400">
                        {{ formatDateTime(order.created_at) }} &middot; {{ order.branch?.name }}
                    </p>
                </div>

                <div class="flex items-center gap-6 text-sm">
                    <span class="rounded-full bg-ink-100 px-3 py-1 font-semibold text-ink-500">
                        {{ order.status_label }}
                    </span>
                    <span class="font-heading font-semibold text-ink-900">{{ formatMoney(order.totals.grand_total) }}</span>
                </div>
            </RouterLink>
        </div>

        <PaginationBar :meta="meta" @change="page = $event" />
    </div>
</template>
