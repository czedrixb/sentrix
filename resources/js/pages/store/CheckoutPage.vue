<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { orders } from '@/api';
import { useCartStore } from '@/stores/cart';
import { useAuthStore } from '@/stores/auth';
import { formatMoney } from '@/support/format';

/**
 * Two-step checkout.
 *
 * The step is a plain ref. The previous site drove this with a jQuery handler
 * bound to `.btn-see`, its generic orange CTA class, so the handler fired on
 * unrelated buttons across the whole site.
 *
 * Note also what this form does not send: no subtotal, no discount, no total.
 * The server prices the order and the confirmation shows what it decided.
 */
const cart = useCartStore();
const auth = useAuthStore();
const router = useRouter();

const step = ref(1);
const submitting = ref(false);
const errors = reactive({});
const generalError = ref(null);

const form = reactive({
    customer_first_name: '',
    customer_last_name: '',
    customer_email: '',
    customer_phone: '',
    fulfillment_type: 'pickup',
    delivery_address: '',
    notes: '',
    scheduled_date: '',
    scheduled_time: '10:00',
});

const minDate = computed(() => new Date().toISOString().slice(0, 10));

/** Delivery is only offered when something in the cart actually needs it. */
const deliveryAvailable = computed(() => cart.requiresDelivery);

const timeSlots = computed(() => {
    const slots = [];

    for (let minutes = 8 * 60; minutes <= 17 * 60; minutes += 30) {
        const hour = String(Math.floor(minutes / 60)).padStart(2, '0');
        const minute = String(minutes % 60).padStart(2, '0');
        slots.push(`${hour}:${minute}`);
    }

    return slots;
});

onMounted(() => {
    if (auth.user) {
        const [first, ...rest] = (auth.user.name ?? '').split(' ');
        form.customer_first_name = first ?? '';
        form.customer_last_name = rest.join(' ');
        form.customer_email = auth.user.email ?? '';
        form.customer_phone = auth.user.phone ?? '';
    }
});

function clearErrors() {
    Object.keys(errors).forEach((key) => delete errors[key]);
    generalError.value = null;
}

async function chooseFulfillment(type) {
    if (type === 'delivery' && !deliveryAvailable.value) {
        return;
    }

    form.fulfillment_type = type;

    try {
        await cart.setFulfillment(type);
    } catch (failure) {
        generalError.value = failure.message;
    }
}

function goToReview() {
    clearErrors();

    const required = {
        customer_first_name: 'First name is required.',
        customer_last_name: 'Last name is required.',
        customer_email: 'Email is required.',
        customer_phone: 'Mobile number is required.',
        scheduled_date: 'Choose a date.',
    };

    for (const [field, message] of Object.entries(required)) {
        if (!form[field]) {
            errors[field] = [message];
        }
    }

    if (form.fulfillment_type === 'delivery' && !form.delivery_address) {
        errors.delivery_address = ['A delivery address is required.'];
    }

    if (Object.keys(errors).length === 0) {
        step.value = 2;
    }
}

async function placeOrder() {
    submitting.value = true;
    clearErrors();

    try {
        const response = await orders.place({
            customer_first_name: form.customer_first_name,
            customer_last_name: form.customer_last_name,
            customer_email: form.customer_email,
            customer_phone: form.customer_phone,
            fulfillment_type: form.fulfillment_type,
            delivery_address: form.fulfillment_type === 'delivery' ? form.delivery_address : null,
            notes: form.notes || null,
            scheduled_for: `${form.scheduled_date} ${form.scheduled_time}:00`,
        });

        await cart.load();

        await router.push({
            name: 'order',
            params: { number: response.data.order_number },
            query: { email: form.customer_email },
        });
    } catch (failure) {
        Object.assign(errors, failure.errors ?? {});
        generalError.value = failure.message;
        step.value = 1;
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="mx-auto max-w-5xl px-4 py-10">
        <h1 class="font-display text-4xl text-ink-700">Checkout</h1>

        <div v-if="cart.isEmpty" class="card mt-8 p-12 text-center">
            <p class="font-heading text-lg font-semibold text-ink-700">There is nothing to check out</p>
            <RouterLink :to="{ name: 'shop' }" class="btn-primary mt-4">Browse the shop</RouterLink>
        </div>

        <div v-else class="mt-6">
            <ol class="mb-8 flex items-center gap-4 text-sm font-heading font-semibold">
                <li :class="step === 1 ? 'text-brand-500' : 'text-slate-400'">1. Your details</li>
                <li class="h-px flex-1 bg-slate-200" />
                <li :class="step === 2 ? 'text-brand-500' : 'text-slate-400'">2. Review &amp; place</li>
            </ol>

            <p v-if="generalError" class="mb-4 rounded bg-red-50 p-3 text-sm text-red-700">{{ generalError }}</p>

            <!-- Step 1 -->
            <form v-show="step === 1" class="grid gap-8 lg:grid-cols-[1fr_18rem]" @submit.prevent="goToReview">
                <div class="space-y-5">
                    <fieldset class="card p-5">
                        <legend class="px-1 font-heading font-bold text-ink-700">Contact</legend>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="field-label" for="first">First name</label>
                                <input id="first" v-model="form.customer_first_name" class="field-input" autocomplete="given-name">
                                <span v-if="errors.customer_first_name" class="field-error">{{ errors.customer_first_name[0] }}</span>
                            </div>
                            <div>
                                <label class="field-label" for="last">Last name</label>
                                <input id="last" v-model="form.customer_last_name" class="field-input" autocomplete="family-name">
                                <span v-if="errors.customer_last_name" class="field-error">{{ errors.customer_last_name[0] }}</span>
                            </div>
                            <div>
                                <label class="field-label" for="email">Email</label>
                                <input id="email" v-model="form.customer_email" type="email" class="field-input" autocomplete="email">
                                <span v-if="errors.customer_email" class="field-error">{{ errors.customer_email[0] }}</span>
                            </div>
                            <div>
                                <label class="field-label" for="phone">Mobile number</label>
                                <div class="flex">
                                    <span class="rounded-l border border-r-0 border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-500">+63</span>
                                    <input id="phone" v-model="form.customer_phone" class="field-input rounded-l-none" placeholder="9171234567" inputmode="numeric">
                                </div>
                                <span v-if="errors.customer_phone" class="field-error">{{ errors.customer_phone[0] }}</span>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="card p-5">
                        <legend class="px-1 font-heading font-bold text-ink-700">How would you like it?</legend>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                type="button"
                                class="rounded border p-4 text-left"
                                :class="form.fulfillment_type === 'pickup' ? 'border-brand-500 bg-brand-50' : 'border-slate-300'"
                                @click="chooseFulfillment('pickup')"
                            >
                                <span class="font-heading font-semibold text-ink-700">Branch pick-up</span>
                                <span class="mt-1 block text-xs text-slate-500">Collect from {{ cart.branch?.name }}</span>
                            </button>

                            <button
                                type="button"
                                class="rounded border p-4 text-left"
                                :class="[
                                    form.fulfillment_type === 'delivery' ? 'border-brand-500 bg-brand-50' : 'border-slate-300',
                                    !deliveryAvailable && 'cursor-not-allowed opacity-50',
                                ]"
                                :disabled="!deliveryAvailable"
                                @click="chooseFulfillment('delivery')"
                            >
                                <span class="font-heading font-semibold text-ink-700">Delivery</span>
                                <span class="mt-1 block text-xs text-slate-500">
                                    {{ deliveryAvailable ? 'Arranged by the branch' : 'Not available for these items' }}
                                </span>
                            </button>
                        </div>

                        <div v-if="form.fulfillment_type === 'delivery'" class="mt-4">
                            <label class="field-label" for="address">Delivery address</label>
                            <textarea id="address" v-model="form.delivery_address" rows="3" class="field-input" />
                            <span v-if="errors.delivery_address" class="field-error">{{ errors.delivery_address[0] }}</span>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="field-label" for="date">Date</label>
                                <input id="date" v-model="form.scheduled_date" type="date" :min="minDate" class="field-input">
                                <span v-if="errors.scheduled_date" class="field-error">{{ errors.scheduled_date[0] }}</span>
                                <span v-if="errors.scheduled_for" class="field-error">{{ errors.scheduled_for[0] }}</span>
                            </div>
                            <div>
                                <label class="field-label" for="time">Time</label>
                                <select id="time" v-model="form.scheduled_time" class="field-input">
                                    <option v-for="slot in timeSlots" :key="slot" :value="slot">{{ slot }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="field-label" for="notes">Notes (optional)</label>
                            <textarea id="notes" v-model="form.notes" rows="2" class="field-input" />
                        </div>
                    </fieldset>

                    <button type="submit" class="btn-primary">Continue to review</button>
                </div>

                <aside class="card h-fit p-5">
                    <h2 class="font-heading font-bold text-ink-700">Order summary</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li v-for="item in cart.items" :key="item.id" class="flex justify-between gap-3">
                            <span class="text-slate-600">{{ item.quantity }} &times; {{ item.product.name }}</span>
                            <span>{{ formatMoney(item.line_total) }}</span>
                        </li>
                    </ul>
                    <div class="mt-4 flex justify-between border-t border-slate-200 pt-3 font-heading font-bold text-ink-700">
                        <span>Total</span>
                        <span>{{ formatMoney(cart.totals.grand_total) }}</span>
                    </div>
                </aside>
            </form>

            <!-- Step 2 -->
            <div v-show="step === 2" class="grid gap-8 lg:grid-cols-[1fr_18rem]">
                <div class="space-y-5">
                    <section class="card p-5">
                        <h2 class="font-heading font-bold text-ink-700">Confirm your details</h2>
                        <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                            <div><dt class="text-slate-500">Name</dt><dd>{{ form.customer_first_name }} {{ form.customer_last_name }}</dd></div>
                            <div><dt class="text-slate-500">Email</dt><dd>{{ form.customer_email }}</dd></div>
                            <div><dt class="text-slate-500">Mobile</dt><dd>+63 {{ form.customer_phone }}</dd></div>
                            <div><dt class="text-slate-500">Branch</dt><dd>{{ cart.branch?.name }}</dd></div>
                            <div>
                                <dt class="text-slate-500">Fulfilment</dt>
                                <dd class="capitalize">{{ form.fulfillment_type }}</dd>
                            </div>
                            <div><dt class="text-slate-500">Scheduled</dt><dd>{{ form.scheduled_date }} {{ form.scheduled_time }}</dd></div>
                            <div v-if="form.delivery_address" class="sm:col-span-2">
                                <dt class="text-slate-500">Address</dt><dd>{{ form.delivery_address }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="card p-5">
                        <h2 class="font-heading font-bold text-ink-700">Payment</h2>
                        <p class="mt-2 text-sm text-slate-600">
                            Payment is settled at the branch on collection or delivery. We will hold your items
                            against this order.
                        </p>
                    </section>

                    <div class="flex gap-3">
                        <button type="button" class="btn-secondary" @click="step = 1">Back</button>
                        <button type="button" class="btn-primary" :disabled="submitting" @click="placeOrder">
                            {{ submitting ? 'Placing order…' : 'Place order' }}
                        </button>
                    </div>
                </div>

                <aside class="card h-fit p-5">
                    <h2 class="font-heading font-bold text-ink-700">Order summary</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li v-for="item in cart.items" :key="item.id" class="flex justify-between gap-3">
                            <span class="text-slate-600">{{ item.quantity }} &times; {{ item.product.name }}</span>
                            <span>{{ formatMoney(item.line_total) }}</span>
                        </li>
                    </ul>
                    <div class="mt-3 space-y-1 border-t border-slate-200 pt-3 text-sm">
                        <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span>{{ formatMoney(cart.totals.subtotal) }}</span></div>
                        <div v-if="Number(cart.totals.discount_total) > 0" class="flex justify-between text-emerald-600">
                            <span>Discount</span><span>&minus;{{ formatMoney(cart.totals.discount_total) }}</span>
                        </div>
                        <div class="flex justify-between font-heading text-lg font-bold text-ink-700">
                            <span>Total</span><span>{{ formatMoney(cart.totals.grand_total) }}</span>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</template>
