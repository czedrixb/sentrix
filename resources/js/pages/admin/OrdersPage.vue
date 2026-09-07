<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { admin } from '@/api';
import { useAuthStore } from '@/stores/auth';
import { useReferenceStore } from '@/stores/reference';
import PaginationBar from '@/components/PaginationBar.vue';
import { formatMoney, formatDateTime } from '@/support/format';

/**
 * Orders.
 *
 * Server-side paging, filtering and sorting -- the previous admin loaded every
 * row and then paginated in the browser. Branch staff are scoped by the API on
 * both the list and every individual action.
 */
const auth = useAuthStore();
const reference = useReferenceStore();

const orders = ref([]);
const meta = ref(null);
const loading = ref(true);
const selected = ref(null);
const busy = ref(false);

const filters = reactive({
    q: '',
    status: '',
    payment_status: '',
    branch_id: '',
    archived: false,
    page: 1,
});

const statuses = [
    { value: 'pending', label: 'For Confirmation' },
    { value: 'confirmed', label: 'Confirmed' },
    { value: 'preparing', label: 'Preparing' },
    { value: 'ready', label: 'Ready for Release' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];

async function load() {
    loading.value = true;

    try {
        const response = await admin.orders({
            q: filters.q || undefined,
            status: filters.status || undefined,
            payment_status: filters.payment_status || undefined,
            branch_id: filters.branch_id || undefined,
            archived: filters.archived ? 1 : undefined,
            page: filters.page,
        });

        orders.value = response.data;
        meta.value = response.meta;
    } finally {
        loading.value = false;
    }
}

async function setStatus(order, status) {
    busy.value = true;

    try {
        const updated = await admin.setOrderStatus(order.order_number, status);
        Object.assign(order, updated);

        if (selected.value?.order_number === order.order_number) {
            selected.value = updated;
        }
    } finally {
        busy.value = false;
    }
}

async function markPaid(order) {
    busy.value = true;

    try {
        Object.assign(order, await admin.setOrderPayment(order.order_number, 'paid'));
    } finally {
        busy.value = false;
    }
}

async function archive(order) {
    await admin.archiveOrder(order.order_number);
    await load();
}

watch(() => [filters.status, filters.payment_status, filters.branch_id, filters.archived], () => {
    filters.page = 1;
    load();
});

watch(() => filters.page, load);

onMounted(load);
</script>

<template>
    <div>
        <header class="mb-6">
            <h1 class="font-heading text-2xl font-semibold text-ink-900">Orders</h1>
            <p v-if="auth.branchName" class="text-sm text-ink-400">Scoped to {{ auth.branchName }}</p>
        </header>

        <div class="card mb-4 flex flex-wrap items-end gap-3 p-4">
            <form class="flex gap-2" @submit.prevent="filters.page = 1; load()">
                <input v-model="filters.q" type="search" placeholder="Order number, name or email" class="field-input w-64">
                <button type="submit" class="btn-primary px-4">Search</button>
            </form>

            <label class="text-sm">
                <span class="field-label">Status</span>
                <select v-model="filters.status" class="field-input">
                    <option value="">All</option>
                    <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                </select>
            </label>

            <label class="text-sm">
                <span class="field-label">Payment</span>
                <select v-model="filters.payment_status" class="field-input">
                    <option value="">All</option>
                    <option value="pending">Unpaid</option>
                    <option value="paid">Paid</option>
                </select>
            </label>

            <!-- Only offered to staff who are not already scoped to one branch. -->
            <label v-if="!auth.branchId" class="text-sm">
                <span class="field-label">Branch</span>
                <select v-model="filters.branch_id" class="field-input">
                    <option value="">All branches</option>
                    <option v-for="branch in reference.branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                </select>
            </label>

            <label class="flex items-center gap-2 pb-2 text-sm">
                <input v-model="filters.archived" type="checkbox" class="rounded">
                Archived
            </label>
        </div>

        <div v-if="loading" class="space-y-3">
            <div v-for="n in 5" :key="n" class="h-20 animate-pulse rounded-2xl bg-white" />
        </div>

        <p v-else-if="orders.length === 0" class="card p-12 text-center text-ink-400">No orders match those filters.</p>

        <div v-else class="card overflow-x-auto">
            <table class="w-full min-w-4xl text-sm">
                <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-400">
                    <tr>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Branch</th>
                        <th class="px-4 py-3">Scheduled</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    <tr v-for="order in orders" :key="order.order_number" class="hover:bg-ink-50">
                        <td class="px-4 py-3">
                            <button type="button" class="font-semibold text-ink-700 hover:underline" @click="selected = order">
                                {{ order.order_number }}
                            </button>
                            <span class="block text-xs text-ink-400">{{ formatDateTime(order.created_at) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            {{ order.customer.first_name }} {{ order.customer.last_name }}
                            <span class="block text-xs text-ink-400">{{ order.customer.email }}</span>
                        </td>
                        <td class="px-4 py-3 text-ink-500">{{ order.branch?.name }}</td>
                        <td class="px-4 py-3 text-ink-500">{{ formatDateTime(order.scheduled_for) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-ink-900">{{ formatMoney(order.totals.grand_total) }}</td>
                        <td class="px-4 py-3">
                            <select
                                :value="order.status"
                                class="rounded border border-ink-200 px-2 py-1 text-xs"
                                :disabled="busy"
                                @change="setStatus(order, $event.target.value)"
                            >
                                <option v-for="status in statuses" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                            <span
                                class="mt-1 block text-xs font-semibold"
                                :class="order.payment_status === 'paid' ? 'text-emerald-600' : 'text-amber-600'"
                            >{{ order.payment_status_label }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button
                                v-if="order.payment_status !== 'paid'"
                                type="button"
                                class="btn-ghost px-2 py-1 text-xs"
                                :disabled="busy"
                                @click="markPaid(order)"
                            >Mark paid</button>
                            <button type="button" class="px-2 py-1 text-xs text-ink-400 hover:underline" @click="archive(order)">
                                Archive
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <PaginationBar :meta="meta" @change="filters.page = $event" />

        <!-- Detail -->
        <div v-if="selected" class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @click.self="selected = null">
            <div class="card max-h-full w-full max-w-2xl overflow-auto p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="font-heading text-lg font-semibold text-ink-900">{{ selected.order_number }}</h2>
                        <p class="text-sm text-ink-400">{{ selected.branch?.name }} &middot; {{ selected.fulfillment_label }}</p>
                    </div>
                    <button type="button" class="btn-ghost" @click="selected = null">Close</button>
                </div>

                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-ink-400">Customer</dt>
                        <dd>{{ selected.customer.first_name }} {{ selected.customer.last_name }}</dd>
                    </div>
                    <div><dt class="text-ink-400">Email</dt><dd>{{ selected.customer.email }}</dd></div>
                    <div><dt class="text-ink-400">Phone</dt><dd>+63 {{ selected.customer.phone }}</dd></div>
                    <div><dt class="text-ink-400">Scheduled</dt><dd>{{ formatDateTime(selected.scheduled_for) }}</dd></div>
                    <div v-if="selected.delivery_address" class="sm:col-span-2">
                        <dt class="text-ink-400">Delivery address</dt><dd>{{ selected.delivery_address }}</dd>
                    </div>
                    <div v-if="selected.notes" class="sm:col-span-2">
                        <dt class="text-ink-400">Notes</dt><dd>{{ selected.notes }}</dd>
                    </div>
                </dl>

                <table class="mt-5 w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-ink-400">
                        <tr>
                            <th class="py-2">Item</th>
                            <th class="py-2 text-right">Unit</th>
                            <th class="py-2 text-right">Qty</th>
                            <th class="py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        <tr v-for="item in selected.items" :key="item.id">
                            <td class="py-2">{{ item.product_name }}<span class="block text-xs text-ink-400">{{ item.sku }}</span></td>
                            <td class="py-2 text-right">{{ formatMoney(item.unit_price) }}</td>
                            <td class="py-2 text-right">{{ item.quantity }}</td>
                            <td class="py-2 text-right">{{ formatMoney(item.line_total) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-3 space-y-1 border-t border-ink-200 pt-3 text-sm">
                    <div class="flex justify-between"><span class="text-ink-400">Subtotal</span><span>{{ formatMoney(selected.totals.subtotal) }}</span></div>
                    <div v-if="Number(selected.totals.discount_total) > 0" class="flex justify-between text-emerald-600">
                        <span>Discount</span><span>&minus;{{ formatMoney(selected.totals.discount_total) }}</span>
                    </div>
                    <div class="flex justify-between font-heading font-semibold text-ink-900">
                        <span>Total</span><span>{{ formatMoney(selected.totals.grand_total) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
