<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

/**
 * Guest order lookup: order number plus the email it was placed with.
 */
const router = useRouter();

const form = reactive({ order_number: '', email: '' });
const error = ref(null);

function submit() {
    error.value = null;

    if (!form.order_number || !form.email) {
        error.value = 'Enter both the order number and the email address used.';

        return;
    }

    router.push({ name: 'order', params: { number: form.order_number }, query: { email: form.email } });
}
</script>

<template>
    <div class="mx-auto max-w-md px-4 py-16">
        <h1 class="font-display text-4xl text-ink-700">Track an order</h1>
        <p class="mt-1 text-sm text-slate-500">No account needed.</p>

        <form class="card mt-8 space-y-4 p-6" @submit.prevent="submit">
            <div>
                <label class="field-label" for="number">Order number</label>
                <input id="number" v-model="form.order_number" class="field-input" placeholder="KMP-260905-0001">
            </div>

            <div>
                <label class="field-label" for="email">Email used</label>
                <input id="email" v-model="form.email" type="email" class="field-input">
            </div>

            <p v-if="error" class="field-error">{{ error }}</p>

            <button type="submit" class="btn-primary w-full">Find my order</button>
        </form>
    </div>
</template>
