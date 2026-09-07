<script setup>
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useCartStore } from '@/stores/cart';
import { formatMoney } from '@/support/format';
import QuantityStepper from '@/components/QuantityStepper.vue';

/**
 * The cart.
 *
 * Every figure shown here is the server's. Nothing on this page adds anything
 * up, which is the whole point: the previous system computed the discount in a
 * template and posted the result back as the amount to charge.
 */
const cart = useCartStore();

const voucherCode = ref('');
const voucherError = ref(null);
const busy = ref(false);
const error = ref(null);

async function run(action) {
    busy.value = true;
    error.value = null;

    try {
        await action();
    } catch (failure) {
        error.value = failure.message;
    } finally {
        busy.value = false;
    }
}

async function applyVoucher() {
    voucherError.value = null;

    try {
        await cart.applyVoucher(voucherCode.value);

        if (cart.notice) {
            voucherError.value = cart.notice;
        }
    } catch (failure) {
        voucherError.value = failure.message;
    }
}

async function removeVoucher() {
    voucherCode.value = '';
    voucherError.value = null;
    await cart.applyVoucher(null);
}
</script>

<template>
    <div class="mx-auto max-w-6xl px-4 py-10">
        <h1 class="font-display text-4xl text-ink-900">Your cart</h1>

        <div v-if="cart.isEmpty" class="card mt-8 p-12 text-center">
            <p class="font-heading text-lg font-semibold text-ink-900">Your cart is empty</p>
            <RouterLink :to="{ name: 'shop' }" class="btn-primary mt-4">Browse the shop</RouterLink>
        </div>

        <div v-else class="mt-8 grid gap-8 lg:grid-cols-[1fr_20rem]">
            <section class="space-y-4">
                <p class="text-sm text-ink-400">
                    Collecting from <strong class="text-ink-900">{{ cart.branch?.name }}</strong>
                </p>

                <article v-for="item in cart.items" :key="item.id" class="card flex gap-4 p-4">
                    <div class="size-24 shrink-0 overflow-hidden rounded bg-ink-100">
                        <img
                            v-if="item.product.images?.[0]"
                            :src="item.product.images[0].url"
                            :alt="item.product.name"
                            class="size-full object-cover"
                        >
                    </div>

                    <div class="flex-1">
                        <RouterLink
                            :to="{ name: 'product', params: { slug: item.product.slug } }"
                            class="font-heading font-semibold text-ink-700 hover:text-brand-600"
                        >
                            {{ item.product.name }}
                        </RouterLink>
                        <p class="text-xs text-ink-400">{{ item.product.sku }}</p>

                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <QuantityStepper
                                :model-value="item.quantity"
                                :min="1"
                                :disabled="busy"
                                @update:model-value="run(() => cart.updateItem(item.id, $event))"
                            />
                            <button type="button" class="text-xs font-semibold text-red-600 hover:underline" @click="run(() => cart.removeItem(item.id))">
                                Remove
                            </button>
                        </div>
                    </div>

                    <div class="text-right">
                        <p class="font-heading font-semibold text-ink-900">{{ formatMoney(item.line_total) }}</p>
                        <p class="text-xs text-ink-400">{{ formatMoney(item.unit_price) }} each</p>
                        <p v-if="cart.voucher && item.discount_eligible" class="mt-1 text-xs text-emerald-600">
                            Voucher applies
                        </p>
                    </div>
                </article>

                <p v-if="error" class="field-error">{{ error }}</p>
            </section>

            <aside class="card h-fit p-5">
                <h2 class="font-heading text-lg font-semibold text-ink-900">Summary</h2>

                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-ink-400">Subtotal</span>
                        <span>{{ formatMoney(cart.totals.subtotal) }}</span>
                    </div>
                    <div v-if="Number(cart.totals.discount_total) > 0" class="flex justify-between text-emerald-600">
                        <span>Discount</span>
                        <span>&minus;{{ formatMoney(cart.totals.discount_total) }}</span>
                    </div>
                    <div v-if="Number(cart.totals.delivery_fee) > 0" class="flex justify-between">
                        <span class="text-ink-400">Delivery</span>
                        <span>{{ formatMoney(cart.totals.delivery_fee) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-ink-200 pt-2 font-heading text-lg font-semibold text-ink-900">
                        <span>Total</span>
                        <span>{{ formatMoney(cart.totals.grand_total) }}</span>
                    </div>
                </div>

                <div class="mt-5 border-t border-ink-200 pt-4">
                    <div v-if="cart.voucher" class="flex items-center justify-between rounded bg-emerald-50 p-2 text-sm">
                        <span class="font-semibold text-emerald-700">{{ cart.voucher.code }} applied</span>
                        <button type="button" class="text-xs text-emerald-700 hover:underline" @click="removeVoucher">
                            Remove
                        </button>
                    </div>

                    <form v-else class="flex gap-2" @submit.prevent="applyVoucher">
                        <input v-model="voucherCode" type="text" placeholder="Voucher code" class="field-input">
                        <button type="submit" class="btn-secondary px-3">Apply</button>
                    </form>

                    <p v-if="voucherError" class="field-error">{{ voucherError }}</p>
                    <p v-else-if="cart.voucherRejectionReason" class="field-error">{{ cart.voucherRejectionReason }}</p>
                </div>

                <RouterLink :to="{ name: 'checkout' }" class="btn-primary mt-5 w-full">Checkout</RouterLink>
            </aside>
        </div>
    </div>
</template>
