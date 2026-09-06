<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { orders } from '@/api';
import { formatMoney, formatDateTime } from '@/support/format';

/**
 * Order confirmation, looked up by number and email.
 *
 * The previous system recovered the order purely from a session cookie after a
 * third-party redirect, so closing the tab or paying on another device left the
 * order stranded. Here the order is addressable.
 */
const route = useRoute();

const order = ref(null);
const loading = ref(true);
const error = ref(null);

onMounted(async () => {
    try {
        order.value = await orders.lookup({
            order_number: route.params.number,
            email: route.query.email,
        });
    } catch {
        error.value = 'We could not find that order. Check the order number and email address.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="mx-auto max-w-3xl px-4 py-12">
        <div v-if="loading" class="h-64 animate-pulse rounded-lg bg-slate-100" />

        <div v-else-if="error" class="card p-10 text-center">
            <p class="font-heading text-lg font-semibold text-ink-700">Order not found</p>
            <p class="mt-2 text-sm text-slate-500">{{ error }}</p>
            <RouterLink :to="{ name: 'track' }" class="btn-primary mt-5">Track an order</RouterLink>
        </div>

        <div v-else>
            <div class="rounded-lg bg-emerald-50 p-6 text-center">
                <p class="font-heading text-xl font-bold text-emerald-800">Thank you, your order is in.</p>
                <p class="mt-1 text-sm text-emerald-700">
                    Order <strong>{{ order.order_number }}</strong> &mdash; keep this number for reference.
                </p>
            </div>

            <section class="card mt-6 p-6">
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Status</dt><dd class="font-semibold text-ink-700">{{ order.status_label }}</dd></div>
                    <div><dt class="text-slate-500">Payment</dt><dd class="font-semibold text-ink-700">{{ order.payment_status_label }}</dd></div>
                    <div><dt class="text-slate-500">Branch</dt><dd>{{ order.branch?.name }}</dd></div>
                    <div><dt class="text-slate-500">Fulfilment</dt><dd>{{ order.fulfillment_label }}</dd></div>
                    <div><dt class="text-slate-500">Scheduled for</dt><dd>{{ formatDateTime(order.scheduled_for) }}</dd></div>
                    <div><dt class="text-slate-500">Placed</dt><dd>{{ formatDateTime(order.created_at) }}</dd></div>
                    <div v-if="order.delivery_address" class="sm:col-span-2">
                        <dt class="text-slate-500">Delivery address</dt><dd>{{ order.delivery_address }}</dd>
                    </div>
                </dl>
            </section>

            <section class="card mt-6 p-6">
                <h2 class="font-heading font-bold text-ink-700">Items</h2>
                <table class="mt-3 w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="py-2">Item</th>
                            <th class="py-2 text-right">Unit</th>
                            <th class="py-2 text-right">Qty</th>
                            <th class="py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in order.items" :key="item.id">
                            <td class="py-2">
                                {{ item.product_name }}
                                <span class="block text-xs text-slate-400">{{ item.sku }}</span>
                            </td>
                            <td class="py-2 text-right">{{ formatMoney(item.unit_price) }}</td>
                            <td class="py-2 text-right">{{ item.quantity }}</td>
                            <td class="py-2 text-right">{{ formatMoney(item.line_total) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-4 space-y-1 border-t border-slate-200 pt-3 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span>{{ formatMoney(order.totals.subtotal) }}</span></div>
                    <div v-if="Number(order.totals.discount_total) > 0" class="flex justify-between text-emerald-600">
                        <span>Discount</span><span>&minus;{{ formatMoney(order.totals.discount_total) }}</span>
                    </div>
                    <div v-if="Number(order.totals.delivery_fee) > 0" class="flex justify-between">
                        <span class="text-slate-500">Delivery</span><span>{{ formatMoney(order.totals.delivery_fee) }}</span>
                    </div>
                    <div class="flex justify-between font-heading text-lg font-bold text-ink-700">
                        <span>Total</span><span>{{ formatMoney(order.totals.grand_total) }}</span>
                    </div>
                </div>
            </section>

            <p class="mt-6 text-center text-sm text-slate-500">
                Payment is settled at the branch. Bring your order number when you collect.
            </p>
        </div>
    </div>
</template>
